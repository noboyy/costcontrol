'use client'

import { useEffect, useState, useCallback } from 'react'
import { getInvestorGallery, buildGalleryServeUrl } from '@/lib/api'
import type { GalleryItem } from '@/lib/types'
import InvestorNav from '@/components/investor/nav'
import { ImageIcon, ExternalLink, X } from 'lucide-react'
import { cn } from '@/lib/utils'

export default function GalleryPage() {
  const [items, setItems] = useState<GalleryItem[]>([])
  const [labels, setLabels] = useState<string[]>([])
  const [activeLabel, setActiveLabel] = useState<string | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [preview, setPreview] = useState<GalleryItem | null>(null)

  const filtered = activeLabel ? items.filter((i) => i.label === activeLabel) : items

  const fetchGallery = useCallback(async () => {
    setLoading(true)
    setError(null)
    try {
      const data = await getInvestorGallery()
      setItems(data.items)
      setLabels(data.labels)
    } catch (e: unknown) {
      setError(e instanceof Error ? e.message : 'Gagal memuat galeri.')
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => { fetchGallery() }, [fetchGallery])

  useEffect(() => {
    function onKey(e: KeyboardEvent) {
      if (e.key === 'Escape') closePreview()
    }
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [])

  function closePreview() {
    setPreview(null)
  }

  return (
    <div className="md:pl-56">
      <InvestorNav />
      <main className="p-4 pb-24 md:p-8">

        {/* Header */}
        <div className="page-header mb-4 flex items-start justify-between">
          <div>
            <h1 className="text-xl font-bold">Galeri Proyek</h1>
            {!loading && !error && (
              <p className="mt-0.5 text-sm text-muted-foreground">
                {filtered.length} file
                {activeLabel && <> · filter: <strong>{activeLabel}</strong></>}
              </p>
            )}
          </div>
        </div>

        {/* Label filters */}
        {!loading && !error && labels.length > 0 && (
          <div className="mb-4 flex flex-wrap gap-2">
            <button
              onClick={() => setActiveLabel(null)}
              className={cn(
                'rounded px-3 py-1 text-sm font-medium border transition-colors',
                activeLabel === null
                  ? 'bg-primary text-primary-foreground border-primary'
                  : 'bg-background border-border text-foreground hover:bg-muted',
              )}
            >
              Semua
            </button>
            {labels.map((label) => (
              <button
                key={label}
                onClick={() => setActiveLabel(label)}
                className={cn(
                  'rounded px-3 py-1 text-sm font-medium border transition-colors',
                  activeLabel === label
                    ? 'bg-primary text-primary-foreground border-primary'
                    : 'bg-background border-border text-foreground hover:bg-muted',
                )}
              >
                {label}
              </button>
            ))}
          </div>
        )}

        {/* Loading skeleton */}
        {loading && (
          <div className="grid gap-4 animate-pulse" style={{ gridTemplateColumns: 'repeat(auto-fill, minmax(200px, 1fr))' }}>
            {[...Array(8)].map((_, i) => (
              <div key={i} className="rounded border overflow-hidden">
                <div className="h-[150px] bg-muted" />
                <div className="p-3 space-y-2">
                  <div className="h-3 bg-muted rounded w-2/3" />
                  <div className="h-3 bg-muted rounded w-1/3" />
                </div>
              </div>
            ))}
          </div>
        )}

        {/* Error */}
        {!loading && error && (
          <div className="rounded border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">
            <p className="mb-3">{error}</p>
            <button
              onClick={fetchGallery}
              className="rounded bg-destructive px-4 py-2 text-sm font-medium text-white hover:bg-destructive/90"
            >
              Coba lagi
            </button>
          </div>
        )}

        {/* Empty state */}
        {!loading && !error && filtered.length === 0 && (
          <div className="rounded border bg-card p-8">
            <div className="flex flex-col items-center gap-3 text-muted-foreground">
              <ImageIcon className="h-10 w-10 opacity-30" />
              <p className="text-sm">Belum ada file di galeri ini.</p>
            </div>
          </div>
        )}

        {/* Gallery grid */}
        {!loading && !error && filtered.length > 0 && (
          <div
            className="grid gap-4"
            style={{ gridTemplateColumns: 'repeat(auto-fill, minmax(200px, 1fr))' }}
          >
            {filtered.map((item) => (
              <GalleryCard
                key={item.id}
                item={item}
                onPreview={() => setPreview(item)}
              />
            ))}
          </div>
        )}

        {/* Lightbox */}
        {preview && (
          <PreviewModal item={preview} onClose={closePreview} />
        )}
      </main>
    </div>
  )
}

// ─── Gallery Card ─────────────────────────────────────────────────────────────

function GalleryCard({ item, onPreview }: { item: GalleryItem; onPreview: () => void }) {
  const url = buildGalleryServeUrl(item.serve_url)

  return (
    <div className="gallery-item flex flex-col overflow-hidden rounded border bg-card transition-shadow hover:shadow-md">
      {/* Thumbnail */}
      <div
        className="gallery-thumb relative flex h-[150px] cursor-pointer items-center justify-center overflow-hidden bg-muted"
        onClick={onPreview}
      >
        {item.file_type === 'image' && (
          // eslint-disable-next-line @next/next/no-img-element
          <img
            src={url}
            alt={item.caption ?? item.original_name}
            className="h-full w-full object-cover transition-transform hover:scale-[1.04]"
            loading="lazy"
          />
        )}
        {item.file_type === 'video' && (
          <div className="flex h-full w-full items-center justify-center bg-blue-50 text-blue-500 dark:bg-blue-950 dark:text-blue-400">
            <svg className="h-12 w-12" fill="currentColor" viewBox="0 0 16 16">
              <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
              <path d="M6.271 5.055a.5.5 0 0 1 .52.038l3.5 2.5a.5.5 0 0 1 0 .814l-3.5 2.5A.5.5 0 0 1 6 10.5v-5a.5.5 0 0 1 .271-.445z"/>
            </svg>
          </div>
        )}
        {item.file_type === 'document' && (
          <div className="flex h-full w-full items-center justify-center bg-red-50 text-red-500 dark:bg-red-950 dark:text-red-400">
            <svg className="h-12 w-12" fill="currentColor" viewBox="0 0 16 16">
              <path d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h5.5L14 4.5zm-3 0A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V4.5h-2z"/>
              <path d="M4.603 14.087a.81.81 0 0 1-.438-.42c-.195-.388-.13-.776.08-1.102.198-.307.526-.568.897-.787a7.68 7.68 0 0 1 1.482-.645 19.697 19.697 0 0 0 1.062-2.227 7.269 7.269 0 0 1-.43-1.295c-.086-.4-.119-.796-.046-1.136.075-.354.274-.672.65-.823.192-.077.4-.12.602-.077a.7.7 0 0 1 .477.365c.088.164.12.356.127.538.007.188-.012.396-.047.614-.084.51-.27 1.134-.52 1.794a10.954 10.954 0 0 0 .98 1.686 5.753 5.753 0 0 1 1.334.05c.364.066.734.195.96.465.12.144.193.32.2.518.007.192-.047.382-.138.563a1.04 1.04 0 0 1-.354.416.856.856 0 0 1-.51.138c-.331-.014-.654-.196-.933-.417a5.712 5.712 0 0 1-.911-.95 11.651 11.651 0 0 0-1.997.406 11.307 11.307 0 0 1-1.02 1.51c-.292.35-.609.656-.927.787a.793.793 0 0 1-.58.029zm1.379-1.901c-.166.076-.32.156-.459.238-.328.194-.541.383-.647.547-.094.145-.096.25-.04.361.01.022.02.036.026.044a.266.266 0 0 0 .035-.012c.137-.056.355-.235.635-.572a8.18 8.18 0 0 0 .45-.606zm1.64-1.33a12.71 12.71 0 0 1 1.01-.193 11.744 11.744 0 0 1-.51-.858 20.801 20.801 0 0 1-.5 1.05zm2.446.45c.15.163.296.3.435.41.24.19.407.253.498.256a.107.107 0 0 0 .07-.015.307.307 0 0 0 .094-.125.436.436 0 0 0 .059-.2.095.095 0 0 0-.026-.063c-.052-.062-.2-.152-.518-.209a3.876 3.876 0 0 0-.612-.053zM8.078 7.8a6.7 6.7 0 0 0 .2-.828c.031-.188.043-.343.038-.465a.613.613 0 0 0-.032-.198.517.517 0 0 0-.145.04c-.087.035-.158.106-.196.283-.04.192-.03.469.046.822.024.111.054.227.09.346z"/>
            </svg>
          </div>
        )}
      </div>

      {/* Info */}
      <div className="flex-1 px-3 pt-2.5 pb-1">
        <div className="flex items-center justify-between gap-2 mb-1">
          <span className="inline-block rounded bg-blue-100 px-2 py-0.5 text-[11px] font-medium text-blue-700 dark:bg-blue-900 dark:text-blue-300">
            {item.label}
          </span>
          <span className="text-[11px] text-muted-foreground whitespace-nowrap">{item.file_size_human}</span>
        </div>
        {item.caption && (
          <p className="mb-1 text-xs text-muted-foreground line-clamp-2">{item.caption}</p>
        )}
        <p className="text-[11px] text-muted-foreground">
          {new Date(item.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })}
          {', '}
          {new Date(item.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}
        </p>
      </div>

    </div>
  )
}

// ─── Preview Modal (Lightbox) ──────────────────────────────────────────────────

function PreviewModal({ item, onClose }: { item: GalleryItem; onClose: () => void }) {
  const url = buildGalleryServeUrl(item.serve_url)

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
      onClick={onClose}
    >
      <div
        className="relative flex flex-col items-center"
        onClick={(e) => e.stopPropagation()}
        style={{ width: 'min(90vw, 900px)' }}
      >
        {/* Close button */}
        <button
          onClick={onClose}
          className="absolute -top-10 right-0 border-none bg-transparent text-3xl text-white cursor-pointer z-10 leading-none"
          aria-label="Tutup"
        >
          <X className="h-7 w-7" />
        </button>

        {/* Media */}
        <div className="flex justify-center items-center min-h-[200px] w-full">
          {item.file_type === 'image' && (
            // eslint-disable-next-line @next/next/no-img-element
            <img
              src={url}
              alt={item.caption ?? item.original_name}
              className="max-h-[75vh] max-w-full rounded-lg object-contain"
            />
          )}
          {item.file_type === 'video' && (
            <video
              src={url}
              controls
              autoPlay={false}
              className="max-h-[75vh] max-w-full rounded-lg"
            />
          )}
          {item.file_type === 'document' && (
            <div className="flex w-full flex-col items-center gap-3">
              <iframe
                src={url}
                title={item.original_name}
                className="h-[75vh] w-full rounded-lg border-0 bg-white"
                style={{ width: 'min(860px, 90vw)' }}
              />
              <a
                href={url}
                target="_blank"
                rel="noopener noreferrer"
                className="flex items-center gap-1 rounded border border-white/40 px-3 py-1.5 text-sm text-white hover:bg-white/10"
              >
                <ExternalLink className="h-4 w-4" /> Buka di tab baru
              </a>
            </div>
          )}
        </div>

        {/* File name */}
        <p className="mt-2 text-center text-[13px] text-white/70">{item.original_name}</p>
      </div>
    </div>
  )
}
