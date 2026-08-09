import type {
  LoginResponse,
  InvestorProjectResponse,
  CostEntry,
  IncomeEntry,
  ReportResponse,
} from './types'

const BASE_URL = process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8000/api/v1'

// ─── Token helpers ────────────────────────────────────────────────────────────

export function getToken(): string | null {
  if (typeof window === 'undefined') return null
  return localStorage.getItem('token')
}

export function setToken(token: string): void {
  localStorage.setItem('token', token)
}

export function clearAuth(): void {
  localStorage.removeItem('token')
  localStorage.removeItem('user')
  localStorage.removeItem('project_id')
}

export function getStoredUser() {
  if (typeof window === 'undefined') return null
  try {
    const raw = localStorage.getItem('user')
    return raw ? JSON.parse(raw) : null
  } catch {
    return null
  }
}

// ─── Core fetch ───────────────────────────────────────────────────────────────

async function apiFetch<T>(
  path: string,
  options: RequestInit = {},
): Promise<T> {
  const token = getToken()
  const res = await fetch(`${BASE_URL}${path}`, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...options.headers,
    },
  })

  if (!res.ok) {
    const body = await res.json().catch(() => ({}))
    throw new ApiError(res.status, body?.message ?? res.statusText)
  }

  return res.json() as Promise<T>
}

export class ApiError extends Error {
  constructor(
    public status: number,
    message: string,
  ) {
    super(message)
    this.name = 'ApiError'
  }
}

// ─── Auth ─────────────────────────────────────────────────────────────────────

export async function login(username: string, password: string): Promise<LoginResponse> {
  return apiFetch<LoginResponse>('/auth/login', {
    method: 'POST',
    body: JSON.stringify({ email: username, password }),
  })
}

export async function logout(): Promise<void> {
  await apiFetch('/auth/logout', { method: 'POST' }).catch(() => {})
  clearAuth()
}

// ─── Investor ─────────────────────────────────────────────────────────────────

export async function getInvestorProject(): Promise<InvestorProjectResponse> {
  return apiFetch<InvestorProjectResponse>('/investor/project')
}

export async function getInvestorCosts(params?: {
  from?: string
  to?: string
}): Promise<{ costs: CostEntry[] }> {
  const qs = new URLSearchParams()
  if (params?.from) qs.set('from', params.from)
  if (params?.to) qs.set('to', params.to)
  const query = qs.toString() ? `?${qs.toString()}` : ''
  return apiFetch(`/investor/project/costs${query}`)
}

export async function getInvestorIncomes(params?: {
  from?: string
  to?: string
}): Promise<{ incomes: IncomeEntry[] }> {
  const qs = new URLSearchParams()
  if (params?.from) qs.set('from', params.from)
  if (params?.to) qs.set('to', params.to)
  const query = qs.toString() ? `?${qs.toString()}` : ''
  return apiFetch(`/investor/project/incomes${query}`)
}

export async function getInvestorReport(params?: {
  from?: string
  to?: string
}): Promise<ReportResponse> {
  const qs = new URLSearchParams()
  if (params?.from) qs.set('from', params.from)
  if (params?.to) qs.set('to', params.to)
  const query = qs.toString() ? `?${qs.toString()}` : ''
  return apiFetch(`/investor/project/report${query}`)
}
