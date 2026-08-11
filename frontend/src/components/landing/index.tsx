import Link from 'next/link'
import { BarChart3, Clock, Shield, Zap, CheckCircle, Phone } from 'lucide-react'

const APP_URL = process.env.NEXT_PUBLIC_APP_URL ?? 'http://localhost:8000'

const features = [
  {
    icon: BarChart3,
    title: 'Kendali Biaya Real-time',
    desc: 'Pantau biaya dan pendapatan proyek secara langsung. Bandingkan target vs realisasi tiap hari.',
  },
  {
    icon: Clock,
    title: 'Daily Close UMKM',
    desc: 'Tutup kas harian otomatis, hitung margin bersih, dan pantau kesehatan bisnis setiap hari.',
  },
  {
    icon: Shield,
    title: 'Multi-Perusahaan',
    desc: 'Kelola banyak unit bisnis dan proyek dalam satu akun. Laporan terpisah per unit.',
  },
  {
    icon: Zap,
    title: 'Laporan & Export',
    desc: 'Rekap biaya per kategori, per proyek, dan per periode. Export CSV kapan saja.',
  },
]

const steps = [
  { num: '01', title: 'Daftar gratis', desc: 'Buat akun dalam 2 menit. Verifikasi email untuk mengaktifkan.' },
  { num: '02', title: 'Tambah unit bisnis', desc: 'Buat proyek atau unit UMKM. Atur target biaya harian/bulanan.' },
  { num: '03', title: 'Catat transaksi', desc: 'Input biaya dan pendapatan tiap hari. Klasifikasikan per kategori.' },
  { num: '04', title: 'Pantau & laporan', desc: 'Lihat dashboard real-time. Ekspor laporan untuk evaluasi.' },
]

export default function LandingPage() {
  return (
    <div className="min-h-screen bg-background text-foreground">
      {/* Navbar */}
      <header className="sticky top-0 z-50 border-b bg-background/95 backdrop-blur">
        <div className="container flex h-14 items-center justify-between">
          <span className="text-lg font-bold text-primary">CostControl</span>
          <nav className="flex items-center gap-2 sm:gap-4">
            <a href="#fitur" className="hidden text-sm text-muted-foreground hover:text-foreground sm:inline">Fitur</a>
            <a href="#cara-kerja" className="hidden text-sm text-muted-foreground hover:text-foreground sm:inline">Cara Kerja</a>
            <a
              href="/login"
              className="text-sm text-muted-foreground hover:text-foreground"
            >
              Masuk
            </a>
            <a
              href={`${APP_URL}/register`}
              className="rounded-md bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground hover:bg-primary/90 sm:px-4"
            >
              Coba Gratis
            </a>
          </nav>
        </div>
      </header>

      {/* Hero */}
      <section className="container py-16 text-center sm:py-24">
        <div className="mx-auto max-w-3xl">
          <span className="mb-4 inline-block rounded-full border bg-muted px-3 py-1 text-xs font-medium text-muted-foreground">
            Trial gratis 14 hari · Tanpa kartu kredit
          </span>
          <h1 className="mb-6 text-3xl font-bold leading-tight tracking-tight sm:text-4xl md:text-5xl">
            Kendali Biaya Proyek &amp; Bisnis{' '}
            <span className="text-primary">dalam Satu Aplikasi</span>
          </h1>
          <p className="mb-8 text-base text-muted-foreground sm:text-lg">
            CostControl membantu kontraktor, konsultan, dan pelaku UMKM memantau biaya,
            pendapatan, dan margin bisnis secara real-time. Tidak perlu spreadsheet rumit.
          </p>
          <div className="flex flex-col items-center gap-3 sm:flex-row sm:justify-center">
            <a
              href={`${APP_URL}/register`}
              className="w-full rounded-lg bg-primary px-8 py-3 font-semibold text-primary-foreground hover:bg-primary/90 sm:w-auto"
            >
              Mulai Trial 14 Hari Gratis
            </a>
            <a
              href="/login"
              className="w-full rounded-lg border px-8 py-3 font-semibold hover:bg-accent sm:w-auto"
            >
              Sudah punya akun? Masuk
            </a>
          </div>
        </div>
      </section>

      {/* Social proof */}
      <section className="border-y bg-muted/30 py-6 sm:py-8">
        <div className="container flex flex-wrap items-center justify-center gap-x-4 gap-y-3 text-xs text-muted-foreground sm:gap-8 sm:text-sm">
          {['Multi-proyek & UMKM', 'Laporan harian otomatis', 'Export CSV', 'Kategori biaya fleksibel', 'Aset & maintenance'].map((t) => (
            <span key={t} className="flex items-center gap-2">
              <CheckCircle className="h-4 w-4 text-primary" /> {t}
            </span>
          ))}
        </div>
      </section>

      {/* Fitur */}
      <section id="fitur" className="container py-16 sm:py-24">
        <div className="mb-10 text-center sm:mb-12">
          <h2 className="text-2xl font-bold sm:text-3xl">Semua yang Kamu Butuhkan</h2>
          <p className="mt-3 text-sm text-muted-foreground sm:text-base">Dari pencatatan transaksi sampai laporan eksekutif.</p>
        </div>
        <div className="grid gap-6 sm:grid-cols-2 sm:gap-8 lg:grid-cols-4">
          {features.map((f) => (
            <div key={f.title} className="rounded-xl border bg-card p-6 shadow-sm">
              <f.icon className="mb-4 h-8 w-8 text-primary" />
              <h3 className="mb-2 font-semibold">{f.title}</h3>
              <p className="text-sm text-muted-foreground">{f.desc}</p>
            </div>
          ))}
        </div>
      </section>

      {/* Cara Kerja */}
      <section id="cara-kerja" className="bg-muted/30 py-16 sm:py-24">
        <div className="container">
          <div className="mb-10 text-center sm:mb-12">
            <h2 className="text-2xl font-bold sm:text-3xl">Mulai dalam 4 Langkah</h2>
            <p className="mt-3 text-sm text-muted-foreground sm:text-base">Setup cepat, langsung bisa dipakai.</p>
          </div>
          <div className="grid gap-6 sm:grid-cols-2 sm:gap-8 lg:grid-cols-4">
            {steps.map((s) => (
              <div key={s.num} className="text-center">
                <div className="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-primary text-lg font-bold text-primary-foreground">
                  {s.num}
                </div>
                <h3 className="mb-2 font-semibold">{s.title}</h3>
                <p className="text-sm text-muted-foreground">{s.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* CTA */}
      <section className="container py-16 text-center sm:py-24">
        <div className="mx-auto max-w-2xl rounded-2xl bg-primary p-8 text-primary-foreground sm:p-12">
          <h2 className="mb-4 text-2xl font-bold sm:text-3xl">Coba Gratis 14 Hari</h2>
          <p className="mb-8 text-sm text-primary-foreground/80 sm:text-base">
            Tidak perlu kartu kredit. Daftar sekarang, verifikasi email, langsung pakai.
          </p>
          <a
            href={`${APP_URL}/register`}
            className="inline-block w-full rounded-lg bg-white px-8 py-3 font-semibold text-primary hover:bg-white/90 sm:w-auto"
          >
            Daftar Sekarang
          </a>
        </div>
      </section>

      {/* Footer */}
      <footer className="border-t py-10">
        <div className="container flex flex-col items-center justify-between gap-3 text-center text-xs text-muted-foreground sm:flex-row sm:gap-4 sm:text-left sm:text-sm">
          <span>© {new Date().getFullYear()} CostControl. Hak cipta dilindungi.</span>
          <div className="flex items-center gap-4">
            <a href="/login" className="hover:text-foreground">Masuk</a>
            <a href={`${APP_URL}/register`} className="hover:text-foreground">Daftar</a>
          </div>
        </div>
      </footer>
    </div>
  )
}
