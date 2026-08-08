'use client'

import { useEffect, useState } from 'react'
import { Loader2, Plus, Pencil, Trash2, Wrench, Tag } from 'lucide-react'
import { toast } from 'sonner'

import { api, ApiError } from '@/lib/api'
import { formatRupiah, formatDate } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'

interface AssetItem {
  id_asset: number
  nama_asset: string
  nilai_asset: number | null
  keterangan?: string | null
  status?: string
  nilai_jual: number | null
  alasan_jual?: string | null
  is_sold: boolean
  maintenance_total: number
  maintenance_count: number
  maintenance: Array<{ id_maintenance: number; tanggal: string; keterangan: string; biaya: number }>
}

export default function AsetPage() {
  const [data, setData] = useState<{ assets: AssetItem[] } | null>(null)
  const [open, setOpen] = useState(false)
  const [editing, setEditing] = useState<AssetItem | null>(null)
  const [sellTarget, setSellTarget] = useState<AssetItem | null>(null)
  const [maintTarget, setMaintTarget] = useState<AssetItem | null>(null)
  const [form, setForm] = useState({ nama_asset: '', nilai_asset: '', keterangan: '' })
  const [sellForm, setSellForm] = useState({ nilai_jual: '', alasan_jual: '', tanggal_jual: '' })
  const [maintForm, setMaintForm] = useState({ tanggal: '', keterangan: '', biaya: '' })

  function load() {
    api.get<{ assets: AssetItem[] }>('/assets').then(setData).catch((e) => toast.error(e.message))
  }

  useEffect(() => {
    load()
  }, [])

  function openCreate() {
    setEditing(null)
    setForm({ nama_asset: '', nilai_asset: '', keterangan: '' })
    setOpen(true)
  }

  function openEdit(a: AssetItem) {
    setEditing(a)
    setForm({ nama_asset: a.nama_asset, nilai_asset: a.nilai_asset != null ? String(a.nilai_asset) : '', keterangan: a.keterangan ?? '' })
    setOpen(true)
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    try {
      if (editing) {
        await api.put(`/assets/${editing.id_asset}`, form)
        toast.success('Asset diperbarui')
      } else {
        await api.post('/assets', form)
        toast.success('Asset ditambahkan')
      }
      setOpen(false)
      load()
    } catch (err) {
      toast.error(err instanceof ApiError ? err.message : 'Gagal')
    }
  }

  async function handleDelete(id: number) {
    if (!confirm('Hapus asset ini?')) return
    try {
      await api.delete(`/assets/${id}`)
      toast.success('Asset dihapus')
      load()
    } catch (err) {
      toast.error(err instanceof ApiError ? err.message : 'Gagal')
    }
  }

  async function handleSell(e: React.FormEvent) {
    e.preventDefault()
    if (!sellTarget) return
    try {
      await api.post(`/assets/${sellTarget.id_asset}/sell`, sellForm)
      setSellTarget(null)
      toast.success('Asset dijual')
      load()
    } catch (err) {
      toast.error(err instanceof ApiError ? err.message : 'Gagal')
    }
  }

  async function handleMaint(e: React.FormEvent) {
    e.preventDefault()
    if (!maintTarget) return
    try {
      await api.post(`/assets/${maintTarget.id_asset}/maintenance`, maintForm)
      setMaintTarget(null)
      toast.success('Maintenance ditambahkan')
      load()
    } catch (err) {
      toast.error(err instanceof ApiError ? err.message : 'Gagal')
    }
  }

  async function handleDelMaint(assetId: number, maintId: number) {
    try {
      await api.delete(`/assets/${assetId}/maintenance/${maintId}`)
      toast.success('Maintenance dihapus')
      load()
    } catch (err) {
      toast.error(err instanceof ApiError ? err.message : 'Gagal')
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">Aset</h1>
          <p className="text-sm text-muted-foreground">Kelola aset perusahaan.</p>
        </div>
        <Dialog open={open} onOpenChange={setOpen}>
          <DialogTrigger asChild>
            <Button onClick={openCreate}>
              <Plus /> Tambah Aset
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>{editing ? 'Ubah Aset' : 'Tambah Aset'}</DialogTitle>
            </DialogHeader>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="space-y-2">
                <Label>Nama Aset</Label>
                <Input
                  value={form.nama_asset}
                  onChange={(e) => setForm({ ...form, nama_asset: e.target.value })}
                  required
                />
              </div>
              <div className="space-y-2">
                <Label>Nilai Aset</Label>
                <Input value={form.nilai_asset} onChange={(e) => setForm({ ...form, nilai_asset: e.target.value })} />
              </div>
              <div className="space-y-2">
                <Label>Keterangan</Label>
                <Input value={form.keterangan} onChange={(e) => setForm({ ...form, keterangan: e.target.value })} />
              </div>
              <DialogFooter>
                <Button type="submit">{editing ? 'Simpan' : 'Tambah'}</Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <Card>
        <CardContent className="p-0">
          {!data ? (
            <div className="flex h-40 items-center justify-center">
              <Loader2 className="h-6 w-6 animate-spin text-primary" />
            </div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Nama</TableHead>
                  <TableHead className="text-right">Nilai</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="text-right">Maintenance</TableHead>
                  <TableHead className="text-right">Aksi</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {data.assets.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={5} className="py-8 text-center text-muted-foreground">
                      Belum ada aset.
                    </TableCell>
                  </TableRow>
                ) : (
                  data.assets.map((a) => (
                    <TableRow key={a.id_asset}>
                      <TableCell className="font-medium">{a.nama_asset}</TableCell>
                      <TableCell className="text-right">{formatRupiah(a.nilai_asset)}</TableCell>
                      <TableCell>
                        <Badge variant={a.is_sold ? 'secondary' : 'success'}>
                          {a.is_sold ? 'Dijual' : 'Ada'}
                        </Badge>
                      </TableCell>
                      <TableCell className="text-right">
                        {a.maintenance_count > 0 ? `${formatRupiah(a.maintenance_total)} (${a.maintenance_count})` : '-'}
                      </TableCell>
                      <TableCell className="text-right">
                        <Button variant="ghost" size="icon" onClick={() => openEdit(a)}>
                          <Pencil />
                        </Button>
                        {!a.is_sold && (
                          <Button variant="ghost" size="icon" onClick={() => { setMaintTarget(a); setMaintForm({ tanggal: '', keterangan: '', biaya: '' }) }}>
                            <Wrench />
                          </Button>
                        )}
                        {!a.is_sold && (
                          <Button variant="ghost" size="icon" onClick={() => { setSellTarget(a); setSellForm({ nilai_jual: '', alasan_jual: '', tanggal_jual: '' }) }}>
                            <Tag />
                          </Button>
                        )}
                        <Button variant="ghost" size="icon" className="text-destructive" onClick={() => handleDelete(a.id_asset)}>
                          <Trash2 />
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      <Dialog open={!!sellTarget} onOpenChange={(o) => !o && setSellTarget(null)}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Jual Aset</DialogTitle>
          </DialogHeader>
          <form onSubmit={handleSell} className="space-y-4">
            <div className="space-y-2">
              <Label>Nilai Jual</Label>
              <Input
                value={sellForm.nilai_jual}
                onChange={(e) => setSellForm({ ...sellForm, nilai_jual: e.target.value })}
                required
              />
            </div>
            <div className="space-y-2">
              <Label>Tanggal Jual</Label>
              <Input
                type="date"
                value={sellForm.tanggal_jual}
                onChange={(e) => setSellForm({ ...sellForm, tanggal_jual: e.target.value })}
              />
            </div>
            <div className="space-y-2">
              <Label>Alasan Jual</Label>
              <Input value={sellForm.alasan_jual} onChange={(e) => setSellForm({ ...sellForm, alasan_jual: e.target.value })} />
            </div>
            <DialogFooter>
              <Button type="submit">Jual</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      <Dialog open={!!maintTarget} onOpenChange={(o) => !o && setMaintTarget(null)}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Tambah Maintenance</DialogTitle>
          </DialogHeader>
          <form onSubmit={handleMaint} className="space-y-4">
            <div className="space-y-2">
              <Label>Tanggal</Label>
              <Input
                type="date"
                value={maintForm.tanggal}
                onChange={(e) => setMaintForm({ ...maintForm, tanggal: e.target.value })}
              />
            </div>
            <div className="space-y-2">
              <Label>Keterangan</Label>
              <Input value={maintForm.keterangan} onChange={(e) => setMaintForm({ ...maintForm, keterangan: e.target.value })} />
            </div>
            <div className="space-y-2">
              <Label>Biaya</Label>
              <Input value={maintForm.biaya} onChange={(e) => setMaintForm({ ...maintForm, biaya: e.target.value })} required />
            </div>
            <DialogFooter>
              <Button type="submit">Simpan</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  )
}