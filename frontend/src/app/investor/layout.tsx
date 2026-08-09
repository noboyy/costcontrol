'use client'

import { useEffect, useState } from 'react'
import { useRouter } from 'next/navigation'
import { getToken, getStoredUser } from '@/lib/api'
import type { User } from '@/lib/types'

export default function InvestorLayout({ children }: { children: React.ReactNode }) {
  const router = useRouter()
  const [checked, setChecked] = useState(false)

  useEffect(() => {
    const token = getToken()
    const user: User | null = getStoredUser()

    if (!token || !user) {
      router.replace('/login')
      return
    }

    if (user.role !== 'INVESTOR') {
      router.replace('/login')
      return
    }

    setChecked(true)
  }, [router])

  if (!checked) {
    return (
      <div className="flex min-h-screen items-center justify-center">
        <div className="h-6 w-6 animate-spin rounded-full border-2 border-primary border-t-transparent" />
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-background text-foreground">
      {children}
    </div>
  )
}
