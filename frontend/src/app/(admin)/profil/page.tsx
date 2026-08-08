'use client'

import { useEffect, useState } from 'react'
import { Loader2 } from 'lucide-react'
import { toast } from 'sonner'

import { api, ApiError } from '@/lib/api'
import { useAuth } from '@/lib/auth'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

interface ProfileData {
  username: string
  role: string
  nama_lengkap: string
  jabatan?: string | null
  no_hp?: string | null
  alamat?: string | null
  id_perusahaan?: number | null
  profile_photo_url?: string | null
}

export default function ProfilPage() {
  const { user } = useAuth()
  const [profile, setProfile] = useState<ProfileData | null>(null)
  const [form, setForm] = useState({ nama_lengkap: '', jabatan: '', no_hp: '', alamat: '' })
  const [pw, setPw] = useState({ current_password: '', new_password: '', new_password_confirmation: '' })

  useEffect(() => {
    api
      .get<ProfileData>('/profile')
      .then((p) => {
        setProfile(p)
        setForm({
          nama_lengkap: p.nama_lengkap ?? '',
          jabatan: p.jabatan ?? '',
          no_hp: p.no_hp ?? '',
          alamat: p.alamat ?? '',
        })
      })
      .catch((e) => toast.error(e.message))
  }, [])

  async function saveData(e: React.FormEvent) {
    e.preventDefault()
    try {
      await api.put('/profile', form)
      toast.success('Data pribadi diperbarui')
    } catch (err) {
      toast.error(err instanceof ApiError ? err.message : 'Gagal')
    }
  }

  async function savePassword(e: React.FormEvent) {
    e.preventDefault()
    try {
      await api.put('/profile/password', pw)
      toast.success('Kata sandi diubah')
      setPw({ current_password: '', new_password: '', new_password_confirmation: '' })
    } catch (err) {
      toast.error(err instanceof ApiError ? err.message : 'Gagal')
    }
  }

  if (!profile) {
    return (
      <div className="flex h-64 items-center justify-center">
        <Loader2 className="h-6 w-6 animate-spin text-primary" />
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold">Profil</h1>
        <p className="text-sm text-muted-foreground">
          {profile.username} · {profile.role}
        </p>
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Data Pribadi</CardTitle>
          </CardHeader>
          <CardContent>
            <form onSubmit={saveData} className="space-y-4">
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
                  <Label>Jabatan</Label>
                  <Input value={form.jabatan} onChange={(e) => setForm({ ...form, jabatan: e.target.value })} />
                </div>
                <div className="space-y-2">
                  <Label>No HP</Label>
                  <Input value={form.no_hp} onChange={(e) => setForm({ ...form, no_hp: e.target.value })} />
                </div>
              </div>
              <div className="space-y-2">
                <Label>Alamat</Label>
                <Input value={form.alamat} onChange={(e) => setForm({ ...form, alamat: e.target.value })} />
              </div>
              <Button type="submit">Simpan</Button>
            </form>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Ubah Kata Sandi</CardTitle>
          </CardHeader>
          <CardContent>
            <form onSubmit={savePassword} className="space-y-4">
              <div className="space-y-2">
                <Label>Kata Sandi Saat Ini</Label>
                <Input
                  type="password"
                  value={pw.current_password}
                  onChange={(e) => setPw({ ...pw, current_password: e.target.value })}
                  required
                />
              </div>
              <div className="space-y-2">
                <Label>Kata Sandi Baru</Label>
                <Input
                  type="password"
                  value={pw.new_password}
                  onChange={(e) => setPw({ ...pw, new_password: e.target.value })}
                  required
                  minLength={6}
                />
              </div>
              <div className="space-y-2">
                <Label>Konfirmasi Kata Sandi Baru</Label>
                <Input
                  type="password"
                  value={pw.new_password_confirmation}
                  onChange={(e) => setPw({ ...pw, new_password_confirmation: e.target.value })}
                  required
                />
              </div>
              {user && (
                <p className="text-xs text-muted-foreground">
                  Login sebagai {user.nama_lengkap}. Setelah ubah kata sandi, Anda akan keluar.
                </p>
              )}
              <Button type="submit">Ubah Kata Sandi</Button>
            </form>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}