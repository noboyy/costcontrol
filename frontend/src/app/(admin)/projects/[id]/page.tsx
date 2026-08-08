'use client'

import { useCallback, useEffect, useState } from 'react'
import { useParams, useRouter } from 'next/navigation'
import { Loader2, Archive, ArchiveRestore } from 'lucide-react'
import { toast } from 'sonner'

import { api, ApiError } from '@/lib/api'
import { formatRupiah } from '@/lib/utils'
import type { Project } from '@/lib/types'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { AdminsCard, EntriesCard, PlansCard } from '@/components/projects/details'

interface ProjectDetail {
  project: Project
  costTypes: Array<{ id_cost_type: number; nama: string }>
  incomeTypes: Array<{ id_income_type: number; nama: string }>
  isArchived: boolean
  summaries: {
    todayCost: number
    todayIncome: number
    todayMargin: number
    monthCost: number
    monthIncome: number
    dailyTarget: number | null
    monthlyTarget: number | null
    dailyUsagePct: number | null
    monthlyUsagePct: number | null
  }
  plans: {
    cost: Array<{ id: number; nama: string; amount: number; actual: number }>
    income: Array<{ id: number; nama: string; amount: number; actual: number }>
    costTotal: number
    incomeTotal: number
  }
  costEntries: Array<{ id: number; tanggal: string; keterangan: string; total: number }>
  incomeEntries: Array<{ id: number; tanggal: string; keterangan: string; total: number }>
  availableAdmins: Array<{ id_pengguna: number; nama_lengkap: string }>
  assignedAdminIds: number[]
}

export default function ProjectDetailPage() {
  const params = useParams<{ id: string }>()
  const router = useRouter()
  const id = params.id
  const [data, setData] = useState<ProjectDetail | null>(null)
  const [error, setError] = useState<string | null>(null)

  const load = useCallback(() => {
    api
      .get<ProjectDetail>(`/projects/${id}`)
      .then(setData)
      .catch((e) => setError(e.message))
  }, [id])

  useEffect(() => {
    load()
  }, [load])

  if (error) {
    return <p className="text-sm text-destructive">{error}</p>
  }

  if (!data) {
    return (
      <div className="flex h-64 items-center justify-center">
        <Loader2 className="h-6 w-6 animate-spin text-primary" />
      </div>
    )
  }

  const p = data.project
  const s = data.summaries

  async function toggleArchive() {
    try {
      await api.post(`/projects/${id}/archive`)
      toast.success(p.is_archived ? 'Unit diaktifkan kembali' : 'Unit diarsipkan')
      load()
    } catch (e) {
      toast.error(e instanceof ApiError ? e.message : 'Gagal')
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex items-start justify-between">
        <div>
          <div className="flex items-center gap-2">
            <h1 className="text-2xl font-bold">{p.nama_project}</h1>
            <Badge variant={p.is_umkm ? 'warning' : 'secondary'}>{p.is_umkm ? 'UMKM' : 'Proyek'}</Badge>
            {p.is_archived && <Badge>Arsip</Badge>}
          </div>
          <p className="text-sm text-muted-foreground">{p.client ?? '-'} · {p.lokasi ?? '-'}</p>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" onClick={toggleArchive}>
            {p.is_archived ? <ArchiveRestore /> : <Archive />}
            {p.is_archived ? 'Aktifkan' : 'Arsipkan'}
          </Button>
          <Button variant="outline" onClick={() => router.push('/projects')}>
            Kembali
          </Button>
        </div>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Stat title="Biaya" value={formatRupiah(s.todayCost)} sub="hari ini" />
        <Stat title="Pendapatan" value={formatRupiah(s.todayIncome)} sub="hari ini" />
        <Stat title="Margin" value={formatRupiah(s.todayMargin)} sub="hari ini" />
        <Stat
          title={p.is_umkm ? 'Target Harian' : 'Target Bulanan'}
          value={
            p.is_umkm
              ? s.dailyTarget != null
                ? formatRupiah(s.dailyTarget)
                : '-'
              : s.monthlyTarget != null
                ? formatRupiah(s.monthlyTarget)
                : '-'
          }
          sub={
            p.is_umkm
              ? s.dailyUsagePct != null
                ? `${s.dailyUsagePct.toFixed(0)}% terpakai`
                : 'UMKM'
              : s.monthlyUsagePct != null
                ? `${s.monthlyUsagePct.toFixed(0)}% terpakai`
                : 'Proyek'
          }
        />
      </div>

      <Tabs defaultValue="cost" className="space-y-4">
        <TabsList>
          <TabsTrigger value="cost">Biaya</TabsTrigger>
          <TabsTrigger value="income">Pendapatan</TabsTrigger>
          <TabsTrigger value="plans">Rencana</TabsTrigger>
          <TabsTrigger value="admins">Admin</TabsTrigger>
        </TabsList>

        <TabsContent value="cost">
          <EntriesCard
            title="Biaya"
            kind="cost"
            types={data.costTypes}
            entries={data.costEntries}
            projectId={id}
            archived={data.isArchived}
            onChanged={load}
          />
        </TabsContent>

        <TabsContent value="income">
          <EntriesCard
            title="Pendapatan"
            kind="income"
            types={data.incomeTypes}
            entries={data.incomeEntries}
            projectId={id}
            archived={data.isArchived}
            onChanged={load}
          />
        </TabsContent>

        <TabsContent value="plans">
          <PlansCard
            costPlans={data.plans.cost}
            incomePlans={data.plans.income}
            costTotal={data.plans.costTotal}
            incomeTotal={data.plans.incomeTotal}
            costTypes={data.costTypes}
            incomeTypes={data.incomeTypes}
            projectId={id}
            archived={data.isArchived}
            onChanged={load}
          />
        </TabsContent>

        <TabsContent value="admins">
          <AdminsCard
            availableAdmins={data.availableAdmins}
            assignedAdminIds={data.assignedAdminIds}
            projectId={id}
            onChanged={load}
          />
        </TabsContent>
      </Tabs>
    </div>
  )
}

function Stat({ title, value, sub }: { title: string; value: string; sub: string }) {
  return (
    <Card>
      <CardHeader className="pb-2">
        <CardTitle className="text-sm font-medium text-muted-foreground">{title}</CardTitle>
      </CardHeader>
      <CardContent>
        <div className="text-xl font-bold">{value}</div>
        <p className="text-xs text-muted-foreground">{sub}</p>
      </CardContent>
    </Card>
  )
}