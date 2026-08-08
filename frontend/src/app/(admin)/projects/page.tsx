'use client'

import { useEffect, useState } from 'react'
import Link from 'next/link'
import { Loader2, Plus } from 'lucide-react'
import { toast } from 'sonner'

import { api, ApiError } from '@/lib/api'
import type { Project } from '@/lib/types'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
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
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

interface ProjectsResponse {
  projects: Project[]
  counts: { all: number; project: number; umkm: number }
  statusFilter: string | null
  modeFilter: string | null
}

export default function ProjectsPage() {
  const [data, setData] = useState<ProjectsResponse | null>(null)
  const [mode, setMode] = useState<'project' | 'umkm'>('project')
  const [open, setOpen] = useState(false)
  const [submitting, setSubmitting] = useState(false)
  const [form, setForm] = useState({
    nama_project: '',
    client: '',
    business_type: '',
    lokasi: '',
    project_value: '',
    monthly_budget: '',
    daily_budget: '',
  })

  function load() {
    api
      .get<ProjectsResponse>(`/projects?mode=${mode}`)
      .then(setData)
      .catch((e) => toast.error(e.message))
  }

  useEffect(() => {
    load()
  }, [mode])

  async function handleCreate(e: React.FormEvent) {
    e.preventDefault()
    setSubmitting(true)
    try {
      await api.post('/projects', { ...form, mode })
      setOpen(false)
      toast.success('Unit berhasil dibuat')
      load()
    } catch (err) {
      toast.error(err instanceof ApiError ? err.message : 'Gagal membuat unit')
    } finally {
      setSubmitting(false)
    }
  }

  if (!data) {
    return (
      <div className="flex h-64 items-center justify-center">
        <Loader2 className="h-6 w-6 animate-spin text-primary" />
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">Unit Bisnis</h1>
          <p className="text-sm text-muted-foreground">Kelola proyek dan unit bisnis Anda.</p>
        </div>
        <Dialog open={open} onOpenChange={setOpen}>
          <DialogTrigger asChild>
            <Button>
              <Plus /> Tambah Unit
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Tambah Unit Baru</DialogTitle>
              <DialogDescription>Buat proyek atau unit bisnis baru.</DialogDescription>
            </DialogHeader>
            <form onSubmit={handleCreate} className="space-y-4">
              <div className="space-y-2">
                <Label>Nama Unit</Label>
                <Input
                  value={form.nama_project}
                  onChange={(e) => setForm({ ...form, nama_project: e.target.value })}
                  required
                />
              </div>
              <div className="grid grid-cols-2 gap-3">
                <div className="space-y-2">
                  <Label>{mode === 'umkm' ? 'Jenis Usaha' : 'Client'}</Label>
                  <Input
                    value={mode === 'umkm' ? form.business_type : form.client}
                    onChange={(e) =>
                      setForm({ ...form, [mode === 'umkm' ? 'business_type' : 'client']: e.target.value })
                    }
                  />
                </div>
                <div className="space-y-2">
                  <Label>Lokasi</Label>
                  <Input
                    value={form.lokasi}
                    onChange={(e) => setForm({ ...form, lokasi: e.target.value })}
                  />
                </div>
              </div>
              <div className="grid grid-cols-2 gap-3">
                <div className="space-y-2">
                  <Label>{mode === 'umkm' ? 'Target Bulanan' : 'Nilai Proyek'}</Label>
                  <Input
                    value={mode === 'umkm' ? form.monthly_budget : form.project_value}
                    onChange={(e) =>
                      setForm({ ...form, [mode === 'umkm' ? 'monthly_budget' : 'project_value']: e.target.value })
                    }
                  />
                </div>
                {mode === 'umkm' && (
                  <div className="space-y-2">
                    <Label>Target Harian</Label>
                    <Input
                      value={form.daily_budget}
                      onChange={(e) => setForm({ ...form, daily_budget: e.target.value })}
                    />
                  </div>
                )}
              </div>
              <DialogFooter>
                <Button type="submit" disabled={submitting}>
                  Simpan
                </Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <div className="flex gap-2">
        <Button
          variant={mode === 'project' ? 'default' : 'outline'}
          size="sm"
          onClick={() => setMode('project')}
        >
          Proyek ({data.counts.project})
        </Button>
        <Button
          variant={mode === 'umkm' ? 'default' : 'outline'}
          size="sm"
          onClick={() => setMode('umkm')}
        >
          UMKM ({data.counts.umkm})
        </Button>
      </div>

      <Card>
        <CardContent className="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Nama</TableHead>
                <TableHead>Client / Jenis Usaha</TableHead>
                <TableHead>Lokasi</TableHead>
                <TableHead>Status</TableHead>
                <TableHead className="text-right">Aksi</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {data.projects.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={5} className="py-8 text-center text-muted-foreground">
                    Belum ada unit.
                  </TableCell>
                </TableRow>
              ) : (
                data.projects.map((p) => (
                  <TableRow key={p.id_project}>
                    <TableCell className="font-medium">{p.nama_project}</TableCell>
                    <TableCell>{mode === 'umkm' ? (p.business_type ?? '-') : (p.client ?? '-')}</TableCell>
                    <TableCell>{p.lokasi ?? '-'}</TableCell>
                    <TableCell>
                      <Badge variant={p.is_archived ? 'secondary' : 'success'}>
                        {p.is_archived ? 'Arsip' : 'Aktif'}
                      </Badge>
                    </TableCell>
                    <TableCell className="text-right">
                      <Button asChild variant="outline" size="sm">
                        <Link href={`/projects/${p.id_project}`}>Buka</Link>
                      </Button>
                    </TableCell>
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  )
}