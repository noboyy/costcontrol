'use client'

import { useEffect, useState } from 'react'
import { Loader2, TrendingDown, TrendingUp, Wallet, ArrowDownCircle, ArrowUpCircle } from 'lucide-react'

import { api } from '@/lib/api'
import { formatRupiah } from '@/lib/utils'
import type { DashboardSummary } from '@/lib/types'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'

function StatCard({
  title,
  value,
  icon: Icon,
  trend,
  trendLabel = 'vs bulan lalu',
}: {
  title: string
  value: string
  icon: React.ElementType
  trend?: number | null
  trendLabel?: string
}) {
  const positive = (trend ?? 0) >= 0
  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
        <CardTitle className="text-sm font-medium text-muted-foreground">{title}</CardTitle>
        <Icon className="h-4 w-4 text-muted-foreground" />
      </CardHeader>
      <CardContent>
        <div className="text-2xl font-bold">{value}</div>
        {trend !== undefined && (
          <p className="mt-1 flex items-center gap-1 text-xs text-muted-foreground">
            {positive ? (
              <TrendingUp className="h-3 w-3 text-emerald-500" />
            ) : (
              <TrendingDown className="h-3 w-3 text-rose-500" />
            )}
            <span className={positive ? 'text-emerald-600' : 'text-rose-600'}>
              {positive ? '+' : ''}
              {Math.round(trend ?? 0)}%
            </span>
            {trendLabel}
          </p>
        )}
      </CardContent>
    </Card>
  )
}

export default function BerandaPage() {
  const [data, setData] = useState<DashboardSummary | null>(null)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    api
      .get<DashboardSummary>('/dashboard')
      .then(setData)
      .catch((e) => setError(e.message))
  }, [])

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

  const weeklyMax = Math.max(
    1,
    ...data.weeklyCost.map((w) => w.value),
    ...(data.weeklyIncome ?? []).map((w) => w.value)
  )

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold">Beranda</h1>
        <p className="text-sm text-muted-foreground">Ringkasan pengendalian biaya proyek Anda.</p>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard
          title="Total Biaya"
          value={formatRupiah(data.totalCost)}
          icon={ArrowDownCircle}
          trend={data.costTrend}
        />
        <StatCard
          title="Total Pendapatan"
          value={formatRupiah(data.totalIncome)}
          icon={ArrowUpCircle}
          trend={data.incomeTrend}
        />
        <StatCard title="Margin" value={formatRupiah(data.totalIncome - data.totalCost)} icon={Wallet} />
        <StatCard
          title="Proyek Aktif"
          value={String(data.activeProjects)}
          icon={Wallet}
          trendLabel="proyek"
          trend={undefined}
        />
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Biaya Mingguan</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="flex h-40 items-end gap-1">
            {data.weeklyCost.map((w) => (
              <div key={w.date} className="flex flex-1 flex-col items-center gap-1">
                <div
                  className="w-full rounded-t bg-primary/80"
                  style={{ height: `${(w.value / weeklyMax) * 100}%` }}
                />
                <span className="text-[10px] text-muted-foreground">{w.label}</span>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Aktivitas Terbaru</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          {Array.isArray(data.recentActivities) && data.recentActivities.length > 0 ? (
            data.recentActivities.map((a) => (
              <div key={`${a.jenis}-${a.id}`} className="flex items-center justify-between border-b pb-2 last:border-0">
                <div>
                  <p className="text-sm font-medium">{a.keterangan}</p>
                  <p className="text-xs text-muted-foreground">
                    {a.tanggal} · {a.tipe ?? '-'}
                  </p>
                </div>
                <Badge variant={a.jenis === 'biaya' ? 'destructive' : 'success'}>
                  {a.jenis === 'biaya' ? '-' : '+'}
                  {formatRupiah(a.total)}
                </Badge>
              </div>
            ))
          ) : (
            <p className="text-sm text-muted-foreground">Belum ada aktivitas.</p>
          )}
        </CardContent>
      </Card>
    </div>
  )
}