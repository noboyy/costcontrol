'use client'

import { useEffect, useState } from 'react'
import { Loader2, Plus, Pencil, Trash2 } from 'lucide-react'
import { toast } from 'sonner'

import { api, ApiError } from '@/lib/api'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'

interface TypeItem {
  id: number
  kode: string
  nama: string
  kategori: string
  default_unit?: string | null
}

interface CategoryGroup {
  kode: string
  nama: string
  icon?: string | null
  warna?: string | null
  types: TypeItem[]
}

interface CategoriesResponse {
  kind: string
  categories: CategoryGroup[]
}

export default function CategoriesPage() {
  const [kind, setKind] = useState<'cost' | 'income'>('cost')
  const [data, setData] = useState<CategoriesResponse | null>(null)
  const [open, setOpen] = useState(false)
  const [editing, setEditing] = useState<TypeItem | null>(null)
  const [form, setForm] = useState({ kode: '', nama: '', kategori: '', default_unit: '' })

  function load() {
    api
      .get<CategoriesResponse>(`/categories/${kind}`)
      .then(setData)
      .catch((e) => toast.error(e.message))
  }

  useEffect(() => {
    load()
  }, [kind])

  function openCreate() {
    setEditing(null)
    setForm({ kode: '', nama: '', kategori: '', default_unit: '' })
    setOpen(true)
  }

  function openEdit(t: TypeItem) {
    setEditing(t)
    setForm({ kode: t.kode, nama: t.nama, kategori: t.kategori, default_unit: t.default_unit ?? '' })
    setOpen(true)
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    try {
      if (editing) {
        await api.put(`/categories/${kind}/${editing.id}`, form)
        toast.success('Tipe diperbarui')
      } else {
        await api.post(`/categories/${kind}`, form)
        toast.success('Tipe ditambahkan')
      }
      setOpen(false)
      load()
    } catch (err) {
      toast.error(err instanceof ApiError ? err.message : 'Gagal')
    }
  }

  async function handleDelete(id: number) {
    try {
      await api.delete(`/categories/${kind}/${id}`)
      toast.success('Tipe dihapus')
      load()
    } catch (err) {
      toast.error(err instanceof ApiError ? err.message : 'Gagal')
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">Kategori</h1>
          <p className="text-sm text-muted-foreground">Kelola tipe biaya dan pendapatan.</p>
        </div>
        <Button onClick={openCreate}>
          <Plus /> Tambah Tipe
        </Button>
      </div>

      <Tabs value={kind} onValueChange={(v) => setKind(v as 'cost' | 'income')}>
        <TabsList>
          <TabsTrigger value="cost">Biaya</TabsTrigger>
          <TabsTrigger value="income">Pendapatan</TabsTrigger>
        </TabsList>
        <TabsContent value={kind}>
          {!data ? (
            <div className="flex h-40 items-center justify-center">
              <Loader2 className="h-6 w-6 animate-spin text-primary" />
            </div>
          ) : (
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
              {data.categories.map((cat) => (
                <Card key={cat.kode}>
                  <CardContent className="p-4">
                    <h3 className="mb-2 font-semibold">{cat.nama}</h3>
                    {cat.types.length === 0 ? (
                      <p className="text-sm text-muted-foreground">Belum ada tipe.</p>
                    ) : (
                      <ul className="space-y-1">
                        {cat.types.map((t) => (
                          <li key={t.id} className="flex items-center justify-between rounded px-2 py-1 hover:bg-muted">
                            <span className="text-sm">{t.nama}</span>
                            <span className="flex gap-1">
                              <Button variant="ghost" size="icon" onClick={() => openEdit(t)}>
                                <Pencil />
                              </Button>
                              <Button
                                variant="ghost"
                                size="icon"
                                className="text-destructive"
                                onClick={() => handleDelete(t.id)}
                              >
                                <Trash2 />
                              </Button>
                            </span>
                          </li>
                        ))}
                      </ul>
                    )}
                  </CardContent>
                </Card>
              ))}
            </div>
          )}
        </TabsContent>
      </Tabs>

      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{editing ? 'Ubah Tipe' : 'Tambah Tipe'}</DialogTitle>
          </DialogHeader>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="grid grid-cols-2 gap-3">
              <div className="space-y-2">
                <Label>Kode</Label>
                <Input value={form.kode} onChange={(e) => setForm({ ...form, kode: e.target.value })} required />
              </div>
              <div className="space-y-2">
                <Label>Kategori</Label>
                <Input
                  value={form.kategori}
                  onChange={(e) => setForm({ ...form, kategori: e.target.value })}
                  placeholder="cek"
                />
              </div>
            </div>
            <div className="space-y-2">
              <Label>Nama</Label>
              <Input value={form.nama} onChange={(e) => setForm({ ...form, nama: e.target.value })} required />
            </div>
            <div className="space-y-2">
              <Label>Satuan Default</Label>
              <Input
                value={form.default_unit}
                onChange={(e) => setForm({ ...form, default_unit: e.target.value })}
              />
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