import type { Metadata } from 'next'
import '@/styles/globals.css'

export const metadata: Metadata = {
  title: 'CostControl — Kendali Biaya Proyek & Bisnis',
  description: 'Aplikasi pengendalian biaya proyek dan bisnis UMKM. Trial gratis 14 hari.',
}

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="id">
      <body className="font-sans antialiased">{children}</body>
    </html>
  )
}
