'use client'

import { useEffect, useState } from 'react'
import { getInvestorCosts } from '@/lib/api'
import type { CostEntry } from '@/lib/types'
import InvestorNav from '@/components/investor/nav'
import { formatRupiah, formatDate } from '@/lib/utils'

export default function InvestorCostsPage() {
  const [costs, setCosts] = useState<CostEntry[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [from, setFrom] = useState('')
  const [to, setTo] = useState('')

  function load(f?: string, t?: string) {
    setLoading(true)
    setError(null)
    getInvestorCosts({ from: f, to: t })
      .then((r) => setCosts(r.costs))
      .catch((e) => setError(e.message))
      .finally(() => setLoading(false))
  }

  useEffect(() => { load() }, [])

  function handleFilter(e: React.FormEvent) {
    e.preventDefault()
    load(from || undefined, to || undefined)
  }

  return (
    <div className="md:pl-56">
      <InvestorNav />
      <main className="p-4 md:p-8">
        <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
          <h1 className="text-xl font-bold">Biaya</h1>
          <form onSubmit={handleFilter} className="flex flex-wrap items-end gap-2">
            <div className="flex flex-col gap-1">
              <label className="text-xs text-muted-foreground">Dari</label>
              <input
                type="date"
                value={from}
                onChange={(e) => setFrom(e.target.value)}
                className="rounded-md border bg-background px-3 py-1.5 text-sm"
              />
            </div>
            <div className="flex flex-col gap-1">
              <label className="text-xs text-muted-foreground">Sampai</label>
              <input
                type="date"
                value={to}
                onChange={(e) => setTo(e.target.value)}
                className="rounded-md border bg-background px-3 py-1.5 text-sm"
              />
            </div>
            <button
              type="submit"
              className="rounded-md bg-primary px-4 py-1.5 text-sm font-medium text-primary-foreground hover:bg-primary/90"
            >
Terapkan
            </button>
            {(from || to) && (
              <button
                type="button"
                onClick={() => { setFrom(''); setTo(''); load() }}
                className="rounded-md border px-4 py-1.5 text-sm hover:bg-accent"
              >
                Atur Ulang
              </button>
            )}
          </form>
        </div>

        {loading && <div className="h-64 animate-pulse rounded-xl bg-muted" />}
        {error && (
          <div className="rounded-xl border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">
            {error}
          </div>
        )}
        {!loading && !error && (
          <>
            <div className="mb-3 flex items-center justify-between text-sm text-muted-foreground">
              <span>{costs.length} transaksi</span>
              <span className="font-medium text-foreground">
                Total: {formatRupiah(costs.reduce((s, c) => s + c.total, 0))}
              </span>
            </div>
            <div className="rounded-xl border overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b bg-muted/40">
                    <th className="px-4 py-2 text-left font-medium">Tanggal</th>
                    <th className="px-4 py-2 text-left font-medium">Keterangan</th>
                    <th className="px-4 py-2 text-left font-medium">Kategori</th>
                    <th className="px-4 py-2 text-right font-medium">Jml</th>
                    <th className="px-4 py-2 text-right font-medium">Harga Satuan</th>
                    <th className="px-4 py-2 text-right font-medium">Total</th>
                  </tr>
                </thead>
                <tbody>
                  {costs.length === 0 ? (
                    <tr>
                      <td colSpan={6} className="px-4 py-8 text-center text-muted-foreground">
                        Tidak ada data
                      </td>
                    </tr>
                  ) : (
                    costs.map((c) => (
                      <tr key={c.id} className="border-b last:border-0 hover:bg-muted/30">
                        <td className="px-4 py-2 whitespace-nowrap">{formatDate(c.tanggal)}</td>
                        <td className="px-4 py-2">
                          <div>{c.keterangan}</div>
                          {c.tipe && <div className="text-xs text-muted-foreground">{c.tipe}</div>}
                        </td>
                        <td className="px-4 py-2 text-muted-foreground">{c.kategori ?? '-'}</td>
                        <td className="px-4 py-2 text-right">
                          {c.qty} {c.unit ?? ''}
                        </td>
                        <td className="px-4 py-2 text-right">{formatRupiah(c.harga_satuan)}</td>
                        <td className="px-4 py-2 text-right font-medium text-red-600">
                          {formatRupiah(c.total)}
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
          </>
        )}
      </main>
    </div>
  )
}
