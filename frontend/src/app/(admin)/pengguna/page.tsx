'use client'

import { useEffect, useState } from 'react'
import { Loader2, Plus, Pencil, Trash2 } from 'lucide-react'
import { toast } from 'sonner'

import { api, ApiError } from '@/lib/api'
import { useAuth } from '@/lib/auth'
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

interface UserItem {
  id_pengguna: number
  nama_lengkap: string
  no_hp?: string | null
  jabatan?: string | null
  username?: string | null
  role?: string | null
  is_active?: string | null
}

export default function PenggunaPage() {
  const { user } = useAuth()
  const [data, setData] = useState<{ users: UserItem[] } | null>(null)
  const [open, setOpen] = useState(false)
  const [editing, setEditing] = useState<UserItem | null>(null)
  const [form, setForm] = useState({
    nama_lengkap: '',
    no_hp: '',
    jabatan: '',
    username: '',
    password: '',
    is_active: '1',
  })

  function load() {
    api.get<{ users: UserItem[] }>('/users').then(setData).catch((e) => toast.error(e.message))
  }

  useEffect(() => {
    load()
  }, [])

  function openCreate() {
    setEditing(null)
    setForm({ nama_lengkap: '', no_hp: '', jabatan: '', username: '', password: '', is_active: '1' })
    setOpen(true)
  }

  function openEdit(u: UserItem) {
    setEditing(u)
    setForm({
      nama_lengkap: u.nama_lengkap,
      no_hp: u.no_hp ?? '',
      jabatan: u.jabatan ?? '',
      username: u.username ?? '',
      password: '',
      is_active: u.is_active ?? '1',
    })
    setOpen(true)
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    const payload: Record<string, unknown> = {
      nama_lengkap: form.nama_lengkap,
      no_hp: form.no_hp,
      jabatan: form.jabatan,
    }
    if (editing) {
      if (form.username) payload.username = form.username
      if (form.password) payload.password = form.password
      payload.is_active = form.is_active
      try {
        await api.put(`/users/${editing.id_pengguna}`, payload)
        toast.success('Pengguna diperbarui')
      } catch (err) {
        toast.error(err instanceof ApiError ? err.message : 'Gagal')
        return
      }
    } else {
      payload.username = form.username
      payload.password = form.password
      try {
        await api.post('/users', payload)
        toast.success('Pengguna ditambahkan')
      } catch (err) {
        toast.error(err instanceof ApiError ? err.message : 'Gagal')
        return
      }
    }
    setOpen(false)
    load()
  }

  async function handleDelete(id: number) {
    if (!confirm('Hapus pengguna ini?')) return
    try {
      await api.delete(`/users/${id}`)
      toast.success('Pengguna dihapus')
      load()
    } catch (err) {
      toast.error(err instanceof ApiError ? err.message : 'Gagal')
    }
  }

  if (user?.role !== 'SUPER ADMIN') {
    return <p className="text-sm text-destructive">Akses ditolak. Hanya SUPER ADMIN.</p>
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">Pengguna</h1>
          <p className="text-sm text-muted-foreground">Kelola akun pengguna.</p>
        </div>
        <Dialog open={open} onOpenChange={setOpen}>
          <DialogTrigger asChild>
            <Button onClick={openCreate}>
              <Plus /> Tambah Pengguna
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>{editing ? 'Ubah Pengguna' : 'Tambah Pengguna'}</DialogTitle>
            </DialogHeader>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="space-y-2">
                <Label>Nama Lengkap</Label>
                <Input
                  value={form.nama_lengkap}
                  onChange={(e) => setForm({ ...form, nama_lengkap: e.target.value })}
                  required
                />
              </div>
              <div className="grid grid-cols-2 gap-3">
                <div className="space-y-2">
                  <Label>No HP</Label>
                  <Input value={form.no_hp} onChange={(e) => setForm({ ...form, no_hp: e.target.value })} />
                </div>
                <div className="space-y-2">
                  <Label>Jabatan</Label>
                  <Input value={form.jabatan} onChange={(e) => setForm({ ...form, jabatan: e.target.value })} />
                </div>
              </div>
              <div className="space-y-2">
                <Label>Username</Label>
                <Input
                  value={form.username}
                  onChange={(e) => setForm({ ...form, username: e.target.value })}
                  required={!editing}
                />
              </div>
              <div className="space-y-2">
                <Label>{editing ? 'Password (kosongkan jika tidak diubah)' : 'Password'}</Label>
                <Input
                  type="password"
                  value={form.password}
                  onChange={(e) => setForm({ ...form, password: e.target.value })}
                  required={!editing}
                  minLength={6}
                />
              </div>
              {editing && (
                <div className="space-y-2">
                  <Label>Status</Label>
                  <select
                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                    value={form.is_active}
                    onChange={(e) => setForm({ ...form, is_active: e.target.value })}
                  >
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                  </select>
                </div>
              )}
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
                  <TableHead>Username</TableHead>
                  <TableHead>Jabatan</TableHead>
                  <TableHead>Role</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="text-right">Aksi</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {data.users.map((u) => (
                  <TableRow key={u.id_pengguna}>
                    <TableCell className="font-medium">{u.nama_lengkap}</TableCell>
                    <TableCell>{u.username}</TableCell>
                    <TableCell>{u.jabatan ?? '-'}</TableCell>
                    <TableCell>
                      <Badge variant={u.role === 'SUPER ADMIN' ? 'default' : 'secondary'}>{u.role}</Badge>
                    </TableCell>
                    <TableCell>
                      <Badge variant={u.is_active === '1' ? 'success' : 'destructive'}>
                        {u.is_active === '1' ? 'Aktif' : 'Nonaktif'}
                      </Badge>
                    </TableCell>
                    <TableCell className="text-right">
                      <Button variant="ghost" size="icon" onClick={() => openEdit(u)}>
                        <Pencil />
                      </Button>
                      {u.role !== 'SUPER ADMIN' && (
                        <Button variant="ghost" size="icon" className="text-destructive" onClick={() => handleDelete(u.id_pengguna)}>
                          <Trash2 />
                        </Button>
                      )}
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>
    </div>
  )
}