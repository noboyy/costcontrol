'use client'

import { useState } from 'react'
import { Plus, Trash2 } from 'lucide-react'
import { toast } from 'sonner'

import { api, ApiError } from '@/lib/api'
import { formatRupiah, formatDate } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'

interface Entry {
  id: number
  tanggal: string
  keterangan: string
  total: number
}

interface EntryForm {
  type_id: string
  tanggal: string
  keterangan: string
  qty: string
  harga_satuan: string
  total: string
}

export function EntriesCard({
  title,
  kind,
  types,
  entries,
  projectId,
  archived,
  onChanged,
}: {
  title: string
  kind: 'cost' | 'income'
  types: Array<{ id_cost_type?: number; id_income_type?: number; nama: string }>
  entries: Entry[]
  projectId: string
  archived: boolean
  onChanged: () => void
}) {
  const [open, setOpen] = useState(false)
  const [submitting, setSubmitting] = useState(false)
  const [form, setForm] = useState<EntryForm>({
    type_id: '',
    tanggal: new Date().toISOString().split('T')[0],
    keterangan: '',
    qty: '',
    harga_satuan: '',
    total: '',
  })

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    setSubmitting(true)
    const typeField = kind === 'cost' ? 'id_cost_type' : 'id_income_type'
    const endpoint = kind === 'cost' ? `/projects/${projectId}/costs` : `/projects/${projectId}/incomes`
    try {
      await api.post(endpoint, {
        [typeField]: Number(form.type_id),
        tanggal: form.tanggal,
        keterangan: form.keterangan,
        qty: form.qty,
        harga_satuan: form.harga_satuan,
        total: form.total,
      })
      setOpen(false)
      toast.success(`${title} ditambahkan`)
      onChanged()
    } catch (err) {
      toast.error(err instanceof ApiError ? err.message : 'Gagal menyimpan')
    } finally {
      setSubmitting(false)
    }
  }

  async function handleDelete(idNum: number) {
    const endpoint =
      kind === 'cost'
        ? `/projects/${projectId}/costs/${idNum}`
        : `/projects/${projectId}/incomes/${idNum}`
    try {
      await api.delete(endpoint)
      toast.success('Entri dihapus')
      onChanged()
    } catch (err) {
      toast.error(err instanceof ApiError ? err.message : 'Gagal menghapus')
    }
  }

  return (
    <Card>
      <CardHeader className="flex-row items-center justify-between space-y-0">
        <CardTitle>{title}</CardTitle>
        {!archived && (
          <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
              <Button size="sm">
                <Plus /> Tambah
              </Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Tambah {title}</DialogTitle>
                <DialogDescription>Isi detail transaksi.</DialogDescription>
              </DialogHeader>
              <form onSubmit={handleSubmit} className="space-y-4">
                <div className="space-y-2">
                  <Label>Jenis</Label>
                  <Select value={form.type_id} onValueChange={(v) => setForm({ ...form, type_id: v })}>
                    <SelectTrigger>
                      <SelectValue placeholder="Pilih jenis" />
                    </SelectTrigger>
                    <SelectContent>
                      {types.map((t) => (
                        <SelectItem
                          key={kind === 'cost' ? t.id_cost_type : t.id_income_type}
                          value={String(kind === 'cost' ? t.id_cost_type : t.id_income_type)}
                        >
                          {t.nama}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                <div className="grid grid-cols-2 gap-3">
                  <div className="space-y-2">
                    <Label>Tanggal</Label>
                    <Input
                      type="date"
                      value={form.tanggal}
                      onChange={(e) => setForm({ ...form, tanggal: e.target.value })}
                      required
                    />
                  </div>
                  <div className="space-y-2">
                    <Label>Qty</Label>
                    <Input
                      value={form.qty}
                      onChange={(e) => setForm({ ...form, qty: e.target.value })}
                      required
                    />
                  </div>
                </div>
                <div className="space-y-2">
                  <Label>Keterangan</Label>
                  <Input
                    value={form.keterangan}
                    onChange={(e) => setForm({ ...form, keterangan: e.target.value })}
                  />
                </div>
                <div className="grid grid-cols-2 gap-3">
                  <div className="space-y-2">
                    <Label>Harga Satuan</Label>
                    <Input
                      value={form.harga_satuan}
                      onChange={(e) => setForm({ ...form, harga_satuan: e.target.value })}
                    />
                  </div>
                  <div className="space-y-2">
                    <Label>Total</Label>
                    <Input value={form.total} onChange={(e) => setForm({ ...form, total: e.target.value })} />
                  </div>
                </div>
                <DialogFooter>
                  <Button type="submit" disabled={submitting}>
                    Simpan
                  </Button>
                </DialogFooter>
              </form>
            </DialogContent>
          </Dialog>
        )}
      </CardHeader>
      <CardContent className="p-0">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Tanggal</TableHead>
              <TableHead>Keterangan</TableHead>
              <TableHead className="text-right">Total</TableHead>
              <TableHead className="text-right">Aksi</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {entries.length === 0 ? (
              <TableRow>
                <TableCell colSpan={4} className="py-8 text-center text-muted-foreground">
                  Belum ada entri.
                </TableCell>
              </TableRow>
            ) : (
              entries.map((e) => (
                <TableRow key={e.id}>
                  <TableCell>{formatDate(e.tanggal)}</TableCell>
                  <TableCell>{e.keterangan || '-'}</TableCell>
                  <TableCell className="text-right">{formatRupiah(e.total)}</TableCell>
                  <TableCell className="text-right">
                    {!archived && (
                      <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => handleDelete(e.id)}
                        className="text-destructive"
                      >
                        <Trash2 />
                      </Button>
                    )}
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </CardContent>
    </Card>
  )
}

interface Plan {
  id: number
  nama: string
  amount: number
  actual: number
}

export function PlansCard({
  costPlans,
  incomePlans,
  costTotal,
  incomeTotal,
  costTypes,
  incomeTypes,
  projectId,
  archived,
  onChanged,
}: {
  costPlans: Plan[]
  incomePlans: Plan[]
  costTotal: number
  incomeTotal: number
  costTypes: Array<{ id_cost_type?: number; id_income_type?: number; nama: string }>
  incomeTypes: Array<{ id_cost_type?: number; id_income_type?: number; nama: string }>
  projectId: string
  archived: boolean
  onChanged: () => void
}) {
  const [open, setOpen] = useState(false)
  const [kind, setKind] = useState<'cost' | 'income'>('cost')
  const [typeId, setTypeId] = useState('')
  const [amount, setAmount] = useState('')

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    try {
      const typeField = kind === 'cost' ? 'id_cost_type' : 'id_income_type'
      await api.post(`/projects/${projectId}/plans/${kind}`, {
        [typeField]: Number(typeId),
        amount,
      })
      setOpen(false)
      toast.success('Rencana disimpan')
      onChanged()
    } catch (err) {
      toast.error(err instanceof ApiError ? err.message : 'Gagal')
    }
  }

  async function handleDelete(planId: number, planKind: 'cost' | 'income') {
    try {
      await api.delete(`/projects/${projectId}/plans/${planKind}/${planId}`)
      toast.success('Rencana dihapus')
      onChanged()
    } catch (err) {
      toast.error(err instanceof ApiError ? err.message : 'Gagal')
    }
  }

  return (
    <div className="space-y-4">
      <div className="grid gap-4 lg:grid-cols-2">
        <PlanTable
          title="Rencana Biaya"
          plans={costPlans}
          total={costTotal}
          onDelete={(id) => handleDelete(id, 'cost')}
          archived={archived}
        />
        <PlanTable
          title="Rencana Pendapatan"
          plans={incomePlans}
          total={incomeTotal}
          onDelete={(id) => handleDelete(id, 'income')}
          archived={archived}
        />
      </div>
      {!archived && (
        <Dialog open={open} onOpenChange={setOpen}>
          <DialogTrigger asChild>
            <Button>
              <Plus /> Tambah Rencana
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Tambah Rencana</DialogTitle>
            </DialogHeader>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="space-y-2">
                <Label>Jenis Rencana</Label>
                <Select value={kind} onValueChange={(v) => setKind(v as 'cost' | 'income')}>
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="cost">Biaya</SelectItem>
                    <SelectItem value="income">Pendapatan</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-2">
                <Label>Jenis</Label>
                <Select value={typeId} onValueChange={setTypeId}>
                  <SelectTrigger>
                    <SelectValue placeholder="Pilih jenis" />
                  </SelectTrigger>
                  <SelectContent>
                    {(kind === 'cost' ? costTypes : incomeTypes).map((t) => {
                      const tid = kind === 'cost' ? t.id_cost_type : t.id_income_type
                      return (
                        <SelectItem key={tid} value={String(tid)}>
                          {t.nama}
                        </SelectItem>
                      )
                    })}
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-2">
                <Label>Jumlah</Label>
                <Input value={amount} onChange={(e) => setAmount(e.target.value)} required />
              </div>
              <DialogFooter>
                <Button type="submit">Simpan</Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      )}
    </div>
  )
}

function PlanTable({
  title,
  plans,
  total,
  onDelete,
  archived,
}: {
  title: string
  plans: Plan[]
  total: number
  onDelete: (id: number) => void
  archived: boolean
}) {
  return (
    <Card>
      <CardHeader className="flex-row items-center justify-between">
        <CardTitle>{title}</CardTitle>
        <span className="text-sm text-muted-foreground">Total {formatRupiah(total)}</span>
      </CardHeader>
      <CardContent className="p-0">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Jenis</TableHead>
              <TableHead className="text-right">Rencana</TableHead>
              <TableHead className="text-right">Realisasi</TableHead>
              {!archived && <TableHead className="text-right">Aksi</TableHead>}
            </TableRow>
          </TableHeader>
          <TableBody>
            {plans.length === 0 ? (
              <TableRow>
                <TableCell colSpan={4} className="py-6 text-center text-muted-foreground">
                  Belum ada rencana.
                </TableCell>
              </TableRow>
            ) : (
              plans.map((p) => (
                <TableRow key={p.id}>
                  <TableCell>{p.nama}</TableCell>
                  <TableCell className="text-right">{formatRupiah(p.amount)}</TableCell>
                  <TableCell className="text-right">{formatRupiah(p.actual)}</TableCell>
                  {!archived && (
                    <TableCell className="text-right">
                      <Button variant="ghost" size="icon" onClick={() => onDelete(p.id)} className="text-destructive">
                        <Trash2 />
                      </Button>
                    </TableCell>
                  )}
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </CardContent>
    </Card>
  )
}

export function AdminsCard({
  availableAdmins,
  assignedAdminIds,
  projectId,
  onChanged,
}: {
  availableAdmins: Array<{ id_pengguna: number; nama_lengkap: string }>
  assignedAdminIds: number[]
  projectId: string
  onChanged: () => void
}) {
  const [selected, setSelected] = useState<number[]>(assignedAdminIds)

  async function save() {
    try {
      await api.put(`/projects/${projectId}/admins`, { admin_ids: selected })
      toast.success('Admin unit diperbarui')
      onChanged()
    } catch (err) {
      toast.error(err instanceof ApiError ? err.message : 'Gagal')
    }
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>Admin Unit</CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="flex flex-wrap gap-2">
          {availableAdmins.map((a) => {
            const checked = selected.includes(a.id_pengguna)
            return (
              <Button
                key={a.id_pengguna}
                type="button"
                variant={checked ? 'default' : 'outline'}
                size="sm"
                onClick={() =>
                  setSelected((prev) =>
                    checked ? prev.filter((x) => x !== a.id_pengguna) : [...prev, a.id_pengguna]
                  )
                }
              >
                {a.nama_lengkap}
              </Button>
            )
          })}
          {availableAdmins.length === 0 && (
            <p className="text-sm text-muted-foreground">Belum ada pengguna tersedia.</p>
          )}
        </div>
        <Button onClick={save}>Simpan Admin</Button>
      </CardContent>
    </Card>
  )
}