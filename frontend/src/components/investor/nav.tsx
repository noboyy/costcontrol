'use client'

import { useEffect, useRef, useState } from 'react'
import Link from 'next/link'
import { usePathname, useRouter } from 'next/navigation'
import { logout } from '@/lib/api'
import { getStoredUser } from '@/lib/api'
import type { User } from '@/lib/types'
import { BarChart3, TrendingDown, TrendingUp, FileText, LogOut, Menu, X, Images } from 'lucide-react'
import { cn } from '@/lib/utils'

const navItems = [
  { href: '/investor', label: 'Dashboard', icon: BarChart3 },
  { href: '/investor/costs', label: 'Biaya', icon: TrendingDown },
  { href: '/investor/incomes', label: 'Pendapatan', icon: TrendingUp },
  { href: '/investor/report', label: 'Laporan', icon: FileText },
  { href: '/investor/gallery', label: 'Galeri', icon: Images },
]

export default function InvestorNav() {
  const pathname = usePathname()
  const router = useRouter()
  const [open, setOpen] = useState(false)
  const [user, setUser] = useState<User | null>(null)

  useEffect(() => {
    setUser(getStoredUser())
  }, [])

  async function handleLogout() {
    await logout()
    router.replace('/login')
  }

  return (
    <>
      {/* Mobile topbar */}
      <header className="sticky top-0 z-40 flex h-14 items-center justify-between border-b bg-background/95 px-4 backdrop-blur md:hidden">
        <span className="font-bold text-primary">CostControl</span>
        <button onClick={() => setOpen(!open)} className="rounded p-1 hover:bg-accent">
          {open ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
        </button>
      </header>

      {/* Mobile drawer */}
      {open && (
        <div className="fixed inset-0 z-30 md:hidden" onClick={() => setOpen(false)}>
          <div
            className="absolute left-0 top-14 w-64 border-r bg-background p-4 shadow-lg"
            onClick={(e) => e.stopPropagation()}
          >
            <p className="mb-3 px-2 text-sm text-muted-foreground">{user?.nama_lengkap}</p>
            <LogoutButton onLogout={handleLogout} />
          </div>
        </div>
      )}

      {/* Mobile bottom nav */}
      <nav className="fixed inset-x-0 bottom-0 z-40 flex h-16 items-stretch border-t bg-background/95 backdrop-blur md:hidden">
        {navItems.map(({ href, label, icon: Icon }) => {
          const active = pathname === href
          return (
            <Link
              key={href}
              href={href}
              className={`flex flex-1 flex-col items-center justify-center gap-1 text-[11px] font-medium ${
                active ? 'text-primary' : 'text-muted-foreground'
              }`}
            >
              <Icon className="h-5 w-5" />
              {label}
            </Link>
          )
        })}
      </nav>

      {/* Desktop sidebar */}
      <aside className="fixed inset-y-0 left-0 hidden w-56 flex-col border-r bg-background p-4 md:flex">
        <div className="mb-6 px-2">
          <span className="font-bold text-primary">CostControl</span>
          {user && (
            <p className="mt-1 truncate text-xs text-muted-foreground">{user.nama_lengkap}</p>
          )}
        </div>
        <NavLinks pathname={pathname} />
        <div className="mt-auto">
          <LogoutButton onLogout={handleLogout} />
        </div>
      </aside>
    </>
  )
}

function NavLinks({ pathname, onNavigate }: { pathname: string; onNavigate?: () => void }) {
  return (
    <nav className="flex flex-col gap-1">
      {navItems.map(({ href, label, icon: Icon }) => (
        <Link
          key={href}
          href={href}
          onClick={onNavigate}
          className={cn(
            'flex items-center gap-3 rounded-md px-3 py-2 text-sm transition-colors',
            pathname === href
              ? 'bg-primary text-primary-foreground'
              : 'text-muted-foreground hover:bg-accent hover:text-foreground',
          )}
        >
          <Icon className="h-4 w-4" />
          {label}
        </Link>
      ))}
    </nav>
  )
}

function LogoutButton({ onLogout }: { onLogout: () => void }) {
  return (
    <button
      onClick={onLogout}
      className="flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
    >
      <LogOut className="h-4 w-4" />
      Keluar
    </button>
  )
}
