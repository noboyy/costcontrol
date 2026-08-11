'use client'

import { useEffect, useState } from 'react'
import { getInvestorProject } from '@/lib/api'
import type { InvestorProjectResponse, RecentDay } from '@/lib/types'
import InvestorNav from '@/components/investor/nav'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { formatRupiah, formatDate } from '@/lib/utils'
import { TrendingDown, TrendingUp, DollarSign, Calendar, Wallet, Activity, ReceiptText, Target, AlertTriangle, Sparkles } from 'lucide-react'

function marginPct(margin: number, income: number): string {
  if (!income) return 'Margin —%'
  const pct = (margin / income) * 100
  return `Margin ${pct.toFixed(1)}%`
}

function budgetPct(d: InvestorProjectResponse): number {
  if (!d.project.project_value) return 0
  return Math.min(100, (d.summaries.totalCost / d.project.project_value) * 100)
}

function budgetBarColor(d: InvestorProjectResponse): string {
  const pct = budgetPct(d)
  if (pct > 100) return 'bg-red-500'
  if (pct > 80) return 'bg-amber-500'
  return 'bg-green-500'
}

export default function InvestorDashboard() {
  const [data, setData] = useState<InvestorProjectResponse | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [loading, setLoading] = useState(true)

  function load() {
    setLoading(true)
    setError(null)
    getInvestorProject()
      .then(setData)
      .catch((e) => setError(e.message))
      .finally(() => setLoading(false))
  }

  useEffect(() => { load() }, [])

  return (
    <div className="md:pl-56">
      <InvestorNav />
      <main className="p-4 pb-24 md:p-8">
        {loading && <LoadingSkeleton />}
        {error && <ErrorBox message={error} onRetry={load} />}
        {data && (
          <div className="flex flex-col gap-6">
            {/* Project header */}
            <div>
              <h1 className="text-2xl font-bold">{data.project.nama_project}</h1>
              <div className="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted-foreground">
                {data.project.client && <span>Klien: {data.project.client}</span>}
                {data.project.lokasi && <span>Lokasi: {data.project.lokasi}</span>}
                {data.project.date_start && (
                  <span className="flex items-center gap-1">
                    <Calendar className="h-3.5 w-3.5" />
                    {formatDate(data.project.date_start)}
                    {data.project.date_end && ` — ${formatDate(data.project.date_end)}`}
                  </span>
                )}
              </div>
            </div>

            {/* Empty state */}
            {data.summaries.totalIncome === 0 && data.summaries.totalCost === 0 && (
              <div className="rounded-xl border border-dashed p-8 text-center">
                <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-muted">
                  <Wallet className="h-6 w-6 text-muted-foreground" />
                </div>
                <h3 className="mb-1 font-semibold">Belum ada transaksi</h3>
                <p className="text-sm text-muted-foreground">
                  Data biaya dan pendapatan akan muncul di sini setelah admin mencatatnya.
                </p>
              </div>
            )}

            {/* Alerts */}
            {data.cashPosition.is_negative && (
              <div className="flex items-center gap-2 rounded-xl border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive">
                <AlertTriangle className="h-4 w-4 shrink-0" />
                <span>Saldo kas negatif. Pengeluaran melebihi pemasukan — segera tinjau arus kas.</span>
              </div>
            )}
            {data.recentDays.filter((d) => d.margin < 0).length >= 3 && (
              <div className="flex items-center gap-2 rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-700">
                <AlertTriangle className="h-4 w-4 shrink-0" />
                <span>Margin negatif terjadi di {data.recentDays.filter((d) => d.margin < 0).length} dari 7 hari terakhir.</span>
              </div>
            )}

            {/* Insight */}
            {data.summaries.totalIncome > 0 && (
              <InsightCard data={data} />
            )}

            {/* Summary cards */}
            <div className="sticky top-14 z-20 grid grid-cols-2 gap-3 bg-background/95 py-2 backdrop-blur md:static lg:grid-cols-4 lg:gap-4">
              <SummaryCard
                title={data.project.mode === 'umkm' ? 'Total Omzet' : 'Total Pendapatan'}
                value={formatRupiah(data.summaries.totalIncome)}
                icon={<TrendingUp className="h-5 w-5 text-green-500" />}
              />
              <SummaryCard
                title="Total Biaya"
                value={formatRupiah(data.summaries.totalCost)}
                icon={<TrendingDown className="h-5 w-5 text-red-500" />}
              />
              <SummaryCard
                title="Margin"
                value={formatRupiah(data.summaries.margin)}
                icon={<DollarSign className="h-5 w-5 text-primary" />}
                highlight={data.summaries.margin >= 0 ? 'positive' : 'negative'}
                sub={marginPct(data.summaries.margin, data.summaries.totalIncome)}
              />
              {data.project.project_value ? (
                <SummaryCard
                  title="Nilai Proyek"
                  value={formatRupiah(data.project.project_value)}
                  icon={<DollarSign className="h-5 w-5 text-muted-foreground" />}
                />
              ) : (
                <SummaryCard
                  title={data.project.mode === 'umkm' ? 'Omzet Hari Ini' : 'Pendapatan Hari Ini'}
                  value={formatRupiah(data.summaries.todayIncome)}
                  icon={<TrendingUp className="h-5 w-5 text-green-500" />}
                />
              )}
            </div>

            {/* Kas berjalan */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <Wallet className="h-5 w-5 text-primary" /> Kas Berjalan
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="flex flex-wrap items-end justify-between gap-4">
                  <div>
                    <div className="text-sm text-muted-foreground">Saldo Kas Hari Ini</div>
                    <div
                      className={`text-2xl font-bold ${
                        data.cashPosition.is_negative ? 'text-red-600' : 'text-green-600'
                      }`}
                    >
                      {formatRupiah(data.cashPosition.balance)}
                    </div>
                    <div className="mt-1 text-xs text-muted-foreground">
                      Per {formatDate(data.cashPosition.date)}
                    </div>
                  </div>
                  <div className="flex flex-wrap gap-x-6 gap-y-2 text-sm">
                    <div>
                      <div className="text-muted-foreground">Saldo Awal</div>
                      <div>{formatRupiah(data.cashPosition.opening)}</div>
                    </div>
                    <div>
                      <div className="text-green-600">Pemasukan</div>
                      <div>{formatRupiah(data.cashPosition.income_to_date)}</div>
                    </div>
                    <div>
                      <div className="text-red-500">Pengeluaran</div>
                      <div>{formatRupiah(data.cashPosition.cost_to_date)}</div>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>

            {/* Cash trend chart */}
            {data.recentDays.length > 1 && (
              <Card>
                <CardHeader>
                  <CardTitle className="flex items-center gap-2">
                    <Activity className="h-5 w-5 text-primary" /> Tren Arus Kas (7 Hari)
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <CashTrendChart data={data.recentDays} />
                </CardContent>
              </Card>
            )}

            {/* Budget realization (project mode) */}
            {data.project.project_value && data.project.mode !== 'umkm' && (
              <Card>
                <CardHeader>
                  <CardTitle className="flex items-center gap-2">
                    <Target className="h-5 w-5 text-primary" /> Realisasi Anggaran
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="mb-2 flex items-center justify-between text-sm">
                    <span className="text-muted-foreground">Biaya terpakai</span>
                    <span className="font-medium">
                      {formatRupiah(data.summaries.totalCost)}
                      <span className="text-muted-foreground"> / {formatRupiah(data.project.project_value)}</span>
                    </span>
                  </div>
                  <div className="h-2.5 w-full overflow-hidden rounded-full bg-muted">
                    <div
                      className={`h-full rounded-full ${budgetBarColor(data)}`}
                      style={{ width: `${budgetPct(data)}%` }}
                    />
                  </div>
                  <div className="mt-2 text-right text-sm font-semibold">
                    {budgetPct(data).toFixed(1)}% terpakai
                  </div>
                </CardContent>
              </Card>
            )}

            {/* Fixed costs (UMKM) */}
            {data.fixedCosts.length > 0 && (
              <Card>
                <CardHeader>
                  <CardTitle className="flex items-center gap-2">
                    <ReceiptText className="h-5 w-5 text-primary" /> Beban Tetap Bulanan
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <ul className="divide-y text-sm">
                    {data.fixedCosts.map((f) => (
                      <li key={f.id} className="flex items-center justify-between py-2">
                        <span>{f.nama}</span>
                        <span className="font-medium">{formatRupiah(f.jumlah)}</span>
                      </li>
                    ))}
                  </ul>
                  <div className="mt-2 flex items-center justify-between border-t pt-2 font-semibold">
                    <span>Total per bulan</span>
                    <span>{formatRupiah(data.fixedCosts.reduce((s, f) => s + f.jumlah, 0))}</span>
                  </div>
                </CardContent>
              </Card>
            )}

            {/* Category breakdown */}
            {(Object.keys(data.categories.byCost).length > 0 || Object.keys(data.categories.byIncome).length > 0) && (
              <div>
                <h2 className="mb-3 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                  Breakdown Per Kategori
                </h2>
                <div className="grid gap-4 md:grid-cols-2">
                  {Object.keys(data.categories.byCost).length > 0 && (
                    <CategoryBreakdown
                      title="Biaya"
                      data={data.categories.byCost}
                      total={data.summaries.totalCost}
                      barClass="bg-red-500"
                    />
                  )}
                  {Object.keys(data.categories.byIncome).length > 0 && (
                    <CategoryBreakdown
                      title={data.project.mode === 'umkm' ? 'Omzet' : 'Pendapatan'}
                      data={data.categories.byIncome}
                      total={data.summaries.totalIncome}
                      barClass="bg-green-500"
                    />
                  )}
                </div>
              </div>
            )}

            {/* This month */}
            <div>
              <h2 className="mb-3 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                Bulan Ini
              </h2>
              <div className="grid grid-cols-2 gap-3 lg:grid-cols-3 lg:gap-4">
                <SummaryCard
                  title={data.project.mode === 'umkm' ? 'Omzet Bulan Ini' : 'Pendapatan Bulan Ini'}
                  value={formatRupiah(data.summaries.monthIncome)}
                  icon={<TrendingUp className="h-5 w-5 text-green-500" />}
                />
                <SummaryCard
                  title="Biaya Bulan Ini"
                  value={formatRupiah(data.summaries.monthCost)}
                  icon={<TrendingDown className="h-5 w-5 text-red-500" />}
                />
                <SummaryCard
                  title="Margin Hari Ini"
                  value={formatRupiah(data.summaries.todayMargin)}
                  icon={<DollarSign className="h-5 w-5 text-primary" />}
                  highlight={data.summaries.todayMargin >= 0 ? 'positive' : 'negative'}
                />
              </div>
            </div>

            {/* Recent days (UMKM) */}
            {data.recentDays.length > 0 && (
              <div>
                <h2 className="mb-3 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                  7 Hari Terakhir
                </h2>
                <div className="hidden md:block rounded-xl border overflow-x-auto">
                  <table className="w-full text-sm">
                    <thead>
                      <tr className="border-b bg-muted/40">
                        <th className="px-4 py-2 text-left font-medium">Tanggal</th>
                        <th className="px-4 py-2 text-right font-medium">Pendapatan</th>
                        <th className="px-4 py-2 text-right font-medium">Biaya</th>
                        <th className="px-4 py-2 text-right font-medium">Margin</th>
                      </tr>
                    </thead>
                    <tbody>
                      {data.recentDays.map((d) => (
                        <tr key={d.date} className="border-b last:border-0 hover:bg-muted/30">
                          <td className="px-4 py-2">{formatDate(d.date)}</td>
                          <td className="px-4 py-2 text-right text-green-600">{formatRupiah(d.income)}</td>
                          <td className="px-4 py-2 text-right text-red-600">{formatRupiah(d.cost_cash)}</td>
                          <td className={`px-4 py-2 text-right font-medium ${d.margin >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                            {formatRupiah(d.margin)}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>

                {/* Mobile cards */}
                <div className="md:hidden space-y-3">
                  {data.recentDays.map((d) => (
                    <div key={d.date} className="rounded-xl border p-4">
                      <div className="mb-1 flex items-center justify-between">
                        <span className="font-medium">{formatDate(d.date)}</span>
                        <span className={`text-sm font-semibold ${d.margin >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                          {formatRupiah(d.margin)}
                        </span>
                      </div>
                      <div className="flex items-center justify-between text-sm">
                        <span className="text-green-600">↑ {formatRupiah(d.income)}</span>
                        <span className="text-red-600">↓ {formatRupiah(d.cost_cash)}</span>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>
        )}
      </main>
    </div>
  )
}

function SummaryCard({
  title,
  value,
  icon,
  highlight,
  sub,
}: {
  title: string
  value: string
  icon: React.ReactNode
  highlight?: 'positive' | 'negative'
  sub?: string
}) {
  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between pb-1 pt-4 px-4">
        <CardTitle className="text-xs font-medium text-muted-foreground">{title}</CardTitle>
        {icon}
      </CardHeader>
      <CardContent className="px-4 pb-4">
        <p
          className={`text-xl font-bold ${
            highlight === 'positive'
              ? 'text-green-600'
              : highlight === 'negative'
                ? 'text-red-600'
                : ''
          }`}
        >
          {value}
        </p>
        {sub && <p className="text-xs text-muted-foreground">{sub}</p>}
      </CardContent>
    </Card>
  )
}

function CategoryBreakdown({
  title,
  data,
  total,
  barClass,
}: {
  title: string
  data: Record<string, number>
  total: number
  barClass: string
}) {
  const entries = Object.entries(data).sort((a, b) => b[1] - a[1])
  const max = Math.max(1, ...entries.map(([, v]) => v))
  const label = (key: string) =>
    key === 'other' ? 'Lainnya' : key.charAt(0).toUpperCase() + key.slice(1).replace(/_/g, ' ')

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-sm font-medium text-muted-foreground">{title}</CardTitle>
      </CardHeader>
      <CardContent className="space-y-3">
        {entries.map(([key, value]) => (
          <div key={key}>
            <div className="mb-1 flex items-center justify-between text-sm">
              <span>{label(key)}</span>
              <span className="font-medium">
                {formatRupiah(value)}
                {total > 0 && <span className="text-muted-foreground"> · {((value / total) * 100).toFixed(0)}%</span>}
              </span>
            </div>
            <div className="h-2 w-full overflow-hidden rounded-full bg-muted">
              <div className={`h-full rounded-full ${barClass}`} style={{ width: `${(value / max) * 100}%` }} />
            </div>
          </div>
        ))}
      </CardContent>
    </Card>
  )
}

function CashTrendChart({ data }: { data: RecentDay[] }) {
  const W = 600
  const H = 200
  const PAD = 10
  const n = data.length
  if (n === 0) return null

  const max = Math.max(1, ...data.map((d) => Math.max(d.income, d.cost_cash)))
  const innerW = W - PAD * 2
  const innerH = H - PAD * 2
  const x = (i: number) => PAD + (i * innerW) / (n - 1 || 1)
  const y = (v: number) => PAD + innerH - (v / max) * innerH
  const path = (key: 'income' | 'cost_cash') =>
    data.map((d, i) => `${i === 0 ? 'M' : 'L'}${x(i).toFixed(1)},${y(d[key]).toFixed(1)}`).join(' ')

  return (
    <div className="w-full overflow-x-auto">
      <svg viewBox={`0 0 ${W} ${H}`} className="w-full min-w-[480px]" role="img" aria-label="Tren arus kas 7 hari">
        <line x1={PAD} y1={H - PAD} x2={W - PAD} y2={H - PAD} stroke="currentColor" className="text-border" />
        <path d={path('income')} fill="none" stroke="#10b981" strokeWidth="2" strokeLinecap="round" />
        <path d={path('cost_cash')} fill="none" stroke="#ef4444" strokeWidth="2" strokeLinecap="round" />
        {data.map((d, i) => (
          <g key={d.date}>
            <circle cx={x(i)} cy={y(d.income)} r="3" fill="#10b981" />
            <circle cx={x(i)} cy={y(d.cost_cash)} r="3" fill="#ef4444" />
          </g>
        ))}
      </svg>
      <div className="mt-2 flex items-center gap-4 text-xs text-muted-foreground">
        <span className="flex items-center gap-1.5">
          <span className="h-2 w-2 rounded-full bg-green-500" /> Pendapatan
        </span>
        <span className="flex items-center gap-1.5">
          <span className="h-2 w-2 rounded-full bg-red-500" /> Biaya
        </span>
        <span className="ml-auto">{data[0].date && formatDate(data[0].date)} – {formatDate(data[n - 1].date)}</span>
      </div>
    </div>
  )
}

function LoadingSkeleton() {
  return (
    <div className="flex flex-col gap-6 animate-pulse">
      <div className="h-8 w-64 rounded bg-muted" />
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {[...Array(4)].map((_, i) => (
          <div key={i} className="h-24 rounded-xl bg-muted" />
        ))}
      </div>
    </div>
  )
}

function ErrorBox({ message, onRetry }: { message: string; onRetry: () => void }) {
  return (
    <div className="rounded-xl border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">
      <p className="mb-3">{message}</p>
      <button
        onClick={onRetry}
        className="rounded-md bg-destructive px-4 py-2 text-sm font-medium text-white hover:bg-destructive/90"
      >
        Coba lagi
      </button>
    </div>
  )
}

function InsightCard({ data }: { data: InvestorProjectResponse }) {
  const { cashPosition, summaries, recentDays, project } = data

  let icon = 'Sparkles'
  let tone = 'text-primary'
  let bg = 'bg-primary/10'
  let text = ''

  // Over budget
  if (project.project_value && summaries.totalCost > project.project_value) {
    icon = 'AlertTriangle'
    tone = 'text-destructive'
    bg = 'bg-destructive/10'
    text = `Biaya ${formatRupiah(summaries.totalCost - project.project_value)} melebihi nilai ${project.mode === 'umkm' ? 'pagu' : 'kontrak'}. Segera evaluasi pengeluaran.`
  }
  // Cash runway
  else if (cashPosition.balance > 0 && recentDays.length > 0) {
    const avgSpend =
      recentDays
        .map((d) => d.cost_cash)
        .reduce((a, b) => a + b, 0) / recentDays.length
    if (avgSpend > 0) {
      const days = Math.floor(cashPosition.balance / avgSpend)
      text =
        days >= 30
          ? `Saldo kas ${formatRupiah(cashPosition.balance)} cukup untuk berjalan lebih dari sebulan.`
          : `Dengan pengeluaran rata-rata ${formatRupiah(avgSpend)}/hari, saldo kas diperkirakan bertahan ${days} hari.`
    }
  }
  // Positive margin
  else if (summaries.margin > 0) {
    text = `Margin ${formatRupiah(summaries.margin)} (${
      marginPct(summaries.margin, summaries.totalIncome).split(' ')[1] ?? ''
    }) — operasional Anda sehat.`
  }

  if (!text) return null

  const Icon = icon === 'AlertTriangle' ? AlertTriangle : Sparkles

  return (
    <div className={`flex items-start gap-3 rounded-xl border px-4 py-3 text-sm ${bg}`}>
      <Icon className={`mt-0.5 h-4 w-4 shrink-0 ${tone}`} />
      <span className={tone}>{text}</span>
    </div>
  )
}
