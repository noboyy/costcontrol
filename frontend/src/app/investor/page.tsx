'use client'

import { useEffect, useState } from 'react'
import { getInvestorProject } from '@/lib/api'
import type { InvestorProjectResponse } from '@/lib/types'
import InvestorNav from '@/components/investor/nav'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { formatRupiah, formatDate } from '@/lib/utils'
import { TrendingDown, TrendingUp, DollarSign, Calendar } from 'lucide-react'

export default function InvestorDashboard() {
  const [data, setData] = useState<InvestorProjectResponse | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    getInvestorProject()
      .then(setData)
      .catch((e) => setError(e.message))
      .finally(() => setLoading(false))
  }, [])

  return (
    <div className="md:pl-56">
      <InvestorNav />
      <main className="p-4 md:p-8">
        {loading && <LoadingSkeleton />}
        {error && <ErrorBox message={error} />}
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

            {/* Summary cards */}
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
              <SummaryCard
                title="Total Pendapatan"
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
              />
              {data.project.project_value && (
                <SummaryCard
                  title="Nilai Proyek"
                  value={formatRupiah(data.project.project_value)}
                  icon={<DollarSign className="h-5 w-5 text-muted-foreground" />}
                />
              )}
            </div>

            {/* This month */}
            <div>
              <h2 className="mb-3 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                Bulan Ini
              </h2>
              <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <SummaryCard
                  title="Pendapatan Bulan Ini"
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
                <div className="rounded-xl border overflow-x-auto">
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
}: {
  title: string
  value: string
  icon: React.ReactNode
  highlight?: 'positive' | 'negative'
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
      </CardContent>
    </Card>
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

function ErrorBox({ message }: { message: string }) {
  return (
    <div className="rounded-xl border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">
      {message}
    </div>
  )
}
