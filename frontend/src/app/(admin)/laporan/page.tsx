'use client'

import { useEffect, useState } from 'react'
import { Loader2, Download } from 'lucide-react'
import { toast } from 'sonner'

import { api, getToken } from '@/lib/api'
import { formatRupiah, formatDate } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'

interface ReportData {
  from: string
  to: string
  units: Array<{ id: number; nama: string; mode: string }>
  totalCost: number
  totalIncome: number
  margin: number
  byCostCategory: Array<{ kategori: string; total: number }>
  byIncomeCategory: Array<{ kategori: string; total: number }>
  byUnit: Array<{ id: number; nama: string; mode: string; cost: number; income: number; margin: number }>
  costs: Array<{ id: number; tanggal: string; unit: string; tipe: string; keterangan: string; total: number }>
  incomes: Array<{ id: number; tanggal: string; unit: string; tipe: string; keterangan: string; total: number }>
}

export default function ReportsPage() {
  const [data, setData] = useState<ReportData | null>(null)
  const [from, setFrom] = useState(() => new Date().toISOString().slice(0, 7) + '-01')
  const [to, setTo] = useState(() => new Date().toISOString().split('T')[0])
  const [projectId, setProjectId] = useState('')

  function load() {
    const q = new URLSearchParams({ from, to })
    if (projectId) q.set('project_id', projectId)
    api
      .get<ReportData>(`/reports?${q.toString()}`)
      .then(setData)
      .catch((e) => toast.error(e.message))
  }

  useEffect(() => {
    load()
  }, [])

  function exportCsv() {
    const q = new URLSearchParams({ from, to })
    if (projectId) q.set('project_id', projectId)
    const token = getToken()
    fetch(`${process.env.NEXT_PUBLIC_API_URL}/reports/export?${q.toString()}`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'text/csv' },
    })
      .then((res) => res.blob())
      .then((blob) => {
        const url = URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = url
        a.download = `laporan_${from}_${to}.csv`
        a.click()
        URL.revokeObjectURL(url)
      })
      .catch(() => toast.error('Gagal mengunduh'))
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">Laporan</h1>
          <p className="text-sm text-muted-foreground">Rekap biaya dan pendapatan.</p>
        </div>
        <Button variant="outline" onClick={exportCsv}>
          <Download /> Export CSV
        </Button>
      </div>

      <Card>
        <CardContent className="grid gap-4 p-4 sm:grid-cols-3">
          <div className="space-y-2">
            <Label>Dari</Label>
            <Input type="date" value={from} onChange={(e) => setFrom(e.target.value)} />
          </div>
          <div className="space-y-2">
            <Label>Sampai</Label>
            <Input type="date" value={to} onChange={(e) => setTo(e.target.value)} />
          </div>
          <div className="space-y-2">
            <Label>Unit</Label>
            <Select value={projectId} onValueChange={setProjectId}>
              <SelectTrigger>
                <SelectValue placeholder="Semua unit" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Semua unit</SelectItem>
                {data?.units.map((u) => (
                  <SelectItem key={u.id} value={String(u.id)}>
                    {u.nama}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            <Button onClick={load} className="mt-2 w-full">
              Terapkan
            </Button>
          </div>
        </CardContent>
      </Card>

      {!data ? (
        <div className="flex h-40 items-center justify-center">
          <Loader2 className="h-6 w-6 animate-spin text-primary" />
        </div>
      ) : (
        <>
          <div className="grid gap-4 sm:grid-cols-3">
            <Card>
              <CardHeader className="pb-2">
                <CardTitle className="text-sm text-muted-foreground">Total Biaya</CardTitle>
              </CardHeader>
              <CardContent className="text-2xl font-bold">{formatRupiah(data.totalCost)}</CardContent>
            </Card>
            <Card>
              <CardHeader className="pb-2">
                <CardTitle className="text-sm text-muted-foreground">Total Pendapatan</CardTitle>
              </CardHeader>
              <CardContent className="text-2xl font-bold">{formatRupiah(data.totalIncome)}</CardContent>
            </Card>
            <Card>
              <CardHeader className="pb-2">
                <CardTitle className="text-sm text-muted-foreground">Margin</CardTitle>
              </CardHeader>
              <CardContent className="text-2xl font-bold">{formatRupiah(data.margin)}</CardContent>
            </Card>
          </div>

          <div className="grid gap-4 lg:grid-cols-2">
            <BreakdownCard title="Biaya per Kategori" rows={data.byCostCategory} />
            <BreakdownCard title="Pendapatan per Kategori" rows={data.byIncomeCategory} />
          </div>

          <Card>
            <CardHeader>
              <CardTitle>Per Unit</CardTitle>
            </CardHeader>
            <CardContent className="p-0">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Unit</TableHead>
                    <TableHead className="text-right">Biaya</TableHead>
                    <TableHead className="text-right">Pendapatan</TableHead>
                    <TableHead className="text-right">Margin</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {data.byUnit.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={4} className="py-6 text-center text-muted-foreground">
                        Belum ada data.
                      </TableCell>
                    </TableRow>
                  ) : (
                    data.byUnit.map((u) => (
                      <TableRow key={u.id}>
                        <TableCell>{u.nama}</TableCell>
                        <TableCell className="text-right">{formatRupiah(u.cost)}</TableCell>
                        <TableCell className="text-right">{formatRupiah(u.income)}</TableCell>
                        <TableCell className="text-right">{formatRupiah(u.margin)}</TableCell>
                      </TableRow>
                    ))
                  )}
                </TableBody>
              </Table>
            </CardContent>
          </Card>

          <DetailTable title="Detail Biaya" rows={data.costs} />
          <DetailTable title="Detail Pendapatan" rows={data.incomes} />
        </>
      )}
    </div>
  )
}

function BreakdownCard({ title, rows }: { title: string; rows: Array<{ kategori: string; total: number }> }) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>{title}</CardTitle>
      </CardHeader>
      <CardContent className="space-y-2">
        {rows.length === 0 ? (
          <p className="text-sm text-muted-foreground">Tidak ada data.</p>
        ) : (
          rows.map((r) => (
            <div key={r.kategori} className="flex items-center justify-between border-b pb-1 last:border-0">
              <span className="text-sm capitalize">{r.kategori}</span>
              <span className="text-sm font-medium">{formatRupiah(r.total)}</span>
            </div>
          ))
        )}
      </CardContent>
    </Card>
  )
}

function DetailTable({
  title,
  rows,
}: {
  title: string
  rows: Array<{ id: number; tanggal: string; unit: string; tipe: string; keterangan: string; total: number }>
}) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>{title}</CardTitle>
      </CardHeader>
      <CardContent className="p-0">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Tanggal</TableHead>
              <TableHead>Unit</TableHead>
              <TableHead>Tipe</TableHead>
              <TableHead>Keterangan</TableHead>
              <TableHead className="text-right">Total</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {rows.length === 0 ? (
              <TableRow>
                <TableCell colSpan={5} className="py-6 text-center text-muted-foreground">
                  Tidak ada data.
                </TableCell>
              </TableRow>
            ) : (
              rows.map((r) => (
                <TableRow key={r.id}>
                  <TableCell>{formatDate(r.tanggal)}</TableCell>
                  <TableCell>{r.unit}</TableCell>
                  <TableCell>{r.tipe ?? '-'}</TableCell>
                  <TableCell>{r.keterangan ?? '-'}</TableCell>
                  <TableCell className="text-right">{formatRupiah(r.total)}</TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </CardContent>
    </Card>
  )
}