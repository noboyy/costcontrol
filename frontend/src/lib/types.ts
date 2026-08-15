export interface User {
  id_akun: number
  username: string
  email: string | null
  role: 'SUPER ADMIN' | 'ADMIN' | 'USER' | 'INVESTOR'
  is_active: '0' | '1'
  nama_lengkap: string
  id_perusahaan: number | null
  trial_ends_at: string | null
  is_trial_expired: boolean
}

export interface LoginResponse {
  token: string
  user: User
  project_id?: number // only for INVESTOR
}

export interface Project {
  id_project: number
  nama_project: string
  client: string | null
  lokasi: string | null
  date_start: string | null
  date_end: string | null
  status: 'active' | 'archived'
  mode: 'project' | 'umkm' | null
  project_value: number | null
}

export interface InvestorSummaries {
  totalCost: number
  totalIncome: number
  margin: number
  todayCost: number
  todayIncome: number
  todayMargin: number
  monthCost: number
  monthIncome: number
}

export interface InvestorProjectResponse {
  project: Project
  summaries: InvestorSummaries
  cashPosition: CashPosition
  dailySnap: Record<string, unknown> | null
  recentDays: RecentDay[]
  fixedCosts: FixedCost[]
  categories: {
    byCost: Record<string, number>
    byIncome: Record<string, number>
  }
}

export interface CashPosition {
  date: string
  opening: number
  income_to_date: number
  cost_to_date: number
  balance: number
  is_negative: boolean
}

export interface RecentDay {
  date: string
  cost_cash: number
  income: number
  margin: number
}

export interface FixedCost {
  id: number
  nama: string
  jumlah: number
}

export interface CostEntry {
  id: number
  tanggal: string
  keterangan: string
  qty: number
  unit: string | null
  harga_satuan: number
  total: number
  catatan: string | null
  tipe: string | null
  kategori: string | null
  file_bukti: string | null
  gallery: GalleryItem[]
}

export interface IncomeEntry {
  id: number
  tanggal: string
  keterangan: string
  qty: number
  unit: string | null
  harga_satuan: number
  total: number
  catatan: string | null
  tipe: string | null
  kategori: string | null
  file_bukti: string | null
  gallery: GalleryItem[]
}

export interface ReportResponse {
  from: string
  to: string
  totalCost: number
  totalIncome: number
  margin: number
  byCostCategory: Record<string, number>
  byIncomeCategory: Record<string, number>
}

export interface GalleryItem {
  id: number
  file_type: 'image' | 'video' | 'document'
  mime_type: string
  label: string
  caption: string | null
  original_name: string
  file_size: number
  file_size_human: string
  created_at: string
  serve_url: string
}

export interface GalleryResponse {
  items: GalleryItem[]
  labels: string[]
}
