export interface Pengguna {
  id_pengguna: number
  id_perusahaan: number | null
  nama_lengkap: string
  no_hp?: string | null
  alamat?: string | null
  jabatan?: string | null
}

export interface Akun {
  id_akun: number
  username: string
  role: string
  is_active: string
  nama_lengkap: string
  id_perusahaan: number | null
  profile_photo_url: string | null
  pengguna?: Pengguna | null
}

export interface LoginResponse {
  token: string
  user: Akun
}

export interface DashboardSummary {
  totalCost: number
  totalIncome: number
  countCost: number
  countIncome: number
  thisMonthCost: number
  lastMonthCost: number
  thisMonthIncome: number
  lastMonthIncome: number
  costTrend?: number
  incomeTrend?: number
  weeklyCost: Array<{ date: string; label: string; value: number }>
  weeklyIncome: Array<{ date: string; label: string; value: number }>
  activeProjects: number
  recentActivities?: Array<{
    id: number
    tanggal: string
    keterangan: string
    total: number
    jenis: 'biaya' | 'pendapatan'
    tipe?: string | null
    project_id?: number | null
  }>
}

export interface Project {
  id_project: number
  nama_project: string
  status: string
  mode: string | null
  budget: number | null
  id_perusahaan: number | null
  business_type?: string | null
  client?: string | null
  lokasi?: string | null
  is_umkm?: boolean
  is_archived?: boolean
}

export interface Paginated<T> {
  data: T[]
  links?: unknown
  meta?: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}