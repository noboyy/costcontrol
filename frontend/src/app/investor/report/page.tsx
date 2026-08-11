'use client'

import { useEffect, useState } from 'react'
import { getInvestorReport } from '@/lib/api'
import type { ReportResponse } from '@/lib/types'
import InvestorNav from '@/components/investor/nav'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { formatRupiah, formatDate } from '@/lib/utils'

function todayStr() {
  return new Date().toISOString().slice(0, 10)
}
function startOfMonthStr() {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-01`
}

export default function InvestorReportPage() {
  const [report, setReport] = useState<ReportResponse | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [from, setFrom] = useState(startOfMonthStr())
  const [to, setTo] = useState(todayStr())

  function load(f: string, t: string) {
    setLoading(true)
    setError(null)
    getInvestorReport({ from: f, to: t })
      .then(setReport)
      .catch((e) => setError(e.message))
      .finally(() => setLoading(false))
  }

  useEffect(() => { load(from, to) }, [])

  function handleFilter(e: React.FormEvent) {
    e.preventDefault()
    load(from, to)
  }

  return (
    <div className="md:pl-56">
      <InvestorNav />
      <main className="p-4 pb-24 md:p-8">
        <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
          <h1 className="text-xl font-bold">Laporan</h1>
          <form onSubmit={handleFilter} className="flex flex-wrap items-end gap-2">
            <div className="flex flex-col gap-1">
              <label className="text-xs text-muted-foreground">Dari</label>
              <input
                type="date"
                value={from}
                onChange={(e) => setFrom(e.target.value)}
                className="rounded-md border bg-background px-3 py-1.5 text-sm"
                required
              />
            </div>
            <div className="flex flex-col gap-1">
              <label className="text-xs text-muted-foreground">Sampai</label>
              <input
                type="date"
                value={to}
                onChange={(e) => setTo(e.target.value)}
                className="rounded-md border bg-background px-3 py-1.5 text-sm"
                required
              />
            </div>
            <button
              type="submit"
              className="rounded-md bg-primary px-4 py-1.5 text-sm font-medium text-primary-foreground hover:bg-primary/90"
            >
              Tampilkan
            </button>
          </form>
        </div>

        {loading && <div className="h-64 animate-pulse rounded-xl bg-muted" />}
        {error && (
          <div className="rounded-xl border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">
            {error}
          </div>
        )}
        {report && !loading && (
          <div className="flex flex-col gap-6">
            <p className="text-sm text-muted-foreground">
              Periode: {formatDate(report.from)} — {formatDate(report.to)}
            </p>

            {/* Summary */}
            <div className="grid gap-4 sm:grid-cols-3">
              <Card>
                <CardHeader className="pb-1 pt-4 px-4">
                  <CardTitle className="text-xs font-medium text-muted-foreground">Total Pendapatan</CardTitle>
                </CardHeader>
                <CardContent className="px-4 pb-4">
                  <p className="text-xl font-bold text-green-600">{formatRupiah(report.totalIncome)}</p>
                </CardContent>
              </Card>
              <Card>
                <CardHeader className="pb-1 pt-4 px-4">
                  <CardTitle className="text-xs font-medium text-muted-foreground">Total Biaya</CardTitle>
                </CardHeader>
                <CardContent className="px-4 pb-4">
                  <p className="text-xl font-bold text-red-600">{formatRupiah(report.totalCost)}</p>
                </CardContent>
              </Card>
              <Card>
                <CardHeader className="pb-1 pt-4 px-4">
                  <CardTitle className="text-xs font-medium text-muted-foreground">Margin</CardTitle>
                </CardHeader>
                <CardContent className="px-4 pb-4">
                  <p className={`text-xl font-bold ${report.margin >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                    {formatRupiah(report.margin)}
                  </p>
                </CardContent>
              </Card>
            </div>

            {/* By category */}
            <div className="grid gap-6 md:grid-cols-2">
              <CategoryTable
                title="Biaya per Kategori"
                data={report.byCostCategory}
                colorClass="text-red-600"
              />
              <CategoryTable
                title="Pendapatan per Kategori"
                data={report.byIncomeCategory}
                colorClass="text-green-600"
              />
            </div>
          </div>
        )}
      </main>
    </div>
  )
}

function CategoryTable({
  title,
  data,
  colorClass,
}: {
  title: string
  data: Record<string, number>
  colorClass: string
}) {
  const entries = Object.entries(data)
  const total = entries.reduce((s, [, v]) => s + v, 0)

  return (
    <div>
      <h2 className="mb-3 text-sm font-semibold">{title}</h2>
      <div className="rounded-xl border overflow-hidden">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b bg-muted/40">
              <th className="px-4 py-2 text-left font-medium">Kategori</th>
              <th className="px-4 py-2 text-right font-medium">Total</th>
              <th className="px-4 py-2 text-right font-medium">Persentase</th>
            </tr>
          </thead>
          <tbody>
            {entries.length === 0 ? (
              <tr>
                <td colSpan={3} className="px-4 py-6 text-center text-muted-foreground">
                  Tidak ada data
                </td>
              </tr>
            ) : (
              entries.map(([key, val]) => (
                <tr key={key} className="border-b last:border-0 hover:bg-muted/30">
                  <td className="px-4 py-2 capitalize">{key}</td>
                  <td className={`px-4 py-2 text-right font-medium ${colorClass}`}>
                    {formatRupiah(val)}
                  </td>
                  <td className="px-4 py-2 text-right text-muted-foreground">
                    {total > 0 ? `${((val / total) * 100).toFixed(1)}%` : '-'}
                  </td>
                </tr>
              ))
            )}
          </tbody>
          {entries.length > 0 && (
            <tfoot>
              <tr className="border-t bg-muted/20">
                <td className="px-4 py-2 font-medium">Total</td>
                <td className={`px-4 py-2 text-right font-bold ${colorClass}`}>
                  {formatRupiah(total)}
                </td>
                <td className="px-4 py-2 text-right text-muted-foreground">100%</td>
              </tr>
            </tfoot>
          )}
        </table>
      </div>
    </div>
  )
}
