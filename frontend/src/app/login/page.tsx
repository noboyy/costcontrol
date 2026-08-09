'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import { login, setToken } from '@/lib/api'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs'

const APP_URL = process.env.NEXT_PUBLIC_APP_URL ?? 'http://localhost:8000'

export default function LoginPage() {
  const router = useRouter()
  const [tab, setTab] = useState('investor')
  const [username, setUsername] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState<string | null>(null)
  const [loading, setLoading] = useState(false)

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    setError(null)
    setLoading(true)

    try {
      const res = await login(username, password)
      setToken(res.token)
      localStorage.setItem('user', JSON.stringify(res.user))

      if (res.user.role === 'INVESTOR') {
        if (res.project_id) {
          localStorage.setItem('project_id', String(res.project_id))
        }
        router.replace('/investor')
      } else {
        window.location.href = APP_URL
      }
    } catch (err: unknown) {
      if (err instanceof Error) {
        setError(err.message)
      } else {
        setError('Terjadi kesalahan. Coba lagi.')
      }
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-background p-4">
      <Card className="w-full max-w-sm">
        <CardHeader className="text-center">
          <CardTitle className="text-2xl font-bold text-primary">CostControl</CardTitle>
          <CardDescription>Masuk ke akun Anda</CardDescription>
        </CardHeader>
        <CardContent>
          <Tabs value={tab} onValueChange={setTab} className="w-full">
            <TabsList className="grid w-full grid-cols-2">
              <TabsTrigger value="investor">Investor</TabsTrigger>
              <TabsTrigger value="admin">Admin / Perusahaan</TabsTrigger>
            </TabsList>

            <TabsContent value="investor">
              <form onSubmit={handleSubmit} className="flex flex-col gap-4">
                <div className="flex flex-col gap-1.5">
                  <Label htmlFor="username">Nama Pengguna</Label>
                  <Input
                    id="username"
                    type="text"
                    autoComplete="username"
                    value={username}
                    onChange={(e) => setUsername(e.target.value)}
                    required
                    disabled={loading}
                  />
                </div>
                <div className="flex flex-col gap-1.5">
                  <Label htmlFor="password">Kata Sandi</Label>
                  <Input
                    id="password"
                    type="password"
                    autoComplete="current-password"
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    required
                    disabled={loading}
                  />
                </div>

                {error && (
                  <p className="rounded-md bg-destructive/10 px-3 py-2 text-sm text-destructive">
                    {error}
                  </p>
                )}

                <Button type="submit" disabled={loading} className="w-full">
                  {loading ? 'Memproses...' : 'Masuk'}
                </Button>
              </form>
            </TabsContent>

            <TabsContent value="admin" className="flex flex-col gap-4">
              <p className="text-sm text-muted-foreground">
                Masuk sebagai admin/perusahaan untuk mengelola master data, unit bisnis, dan laporan.
              </p>
              <Button
                type="button"
                className="w-full"
                onClick={() => (window.location.href = `${APP_URL}/login`)}
              >
                Masuk sebagai Admin
              </Button>
              <p className="text-center text-xs text-muted-foreground">
                Belum punya akun?{' '}
                <a href={`${APP_URL}/register`} className="font-medium text-primary hover:underline">
                  Coba gratis 14 hari
                </a>
              </p>
            </TabsContent>
          </Tabs>
        </CardContent>
      </Card>
    </div>
  )
}