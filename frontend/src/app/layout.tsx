import type { Metadata } from 'next'

import { AuthProvider } from '@/lib/auth'
import { Toaster } from '@/components/ui/sonner'

import '@/styles/globals.css'

export const metadata: Metadata = {
  title: 'CostControl',
  description: 'Aplikasi pengendalian biaya proyek',
}

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="id">
      <body className="font-sans antialiased">
        <AuthProvider>
          {children}
          <Toaster />
        </AuthProvider>
      </body>
    </html>
  )
}