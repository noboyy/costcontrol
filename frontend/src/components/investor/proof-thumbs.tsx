import { Film, FileText } from 'lucide-react'
import { buildGalleryServeUrl } from '@/lib/api'
import type { GalleryItem } from '@/lib/types'

export default function ProofThumbs({ items }: { items: GalleryItem[] }) {
  if (!items?.length) return null

  return (
    <div className="mt-2 flex flex-wrap gap-1.5">
      {items.map((g) => {
        const url = buildGalleryServeUrl(g.serve_url)
        return (
          <a
            key={g.id}
            href={url}
            target="_blank"
            rel="noreferrer"
            title={g.original_name}
          >
            {g.file_type === 'image' ? (
              // eslint-disable-next-line @next/next/no-img-element
              <img
                src={url}
                alt={g.original_name}
                className="h-9 w-9 rounded border object-cover"
              />
            ) : (
              <span
                className={`flex h-9 w-9 items-center justify-center rounded border text-muted-foreground ${
                  g.file_type === 'video' ? 'bg-blue-50 text-blue-600' : 'bg-red-50 text-red-600'
                }`}
              >
                {g.file_type === 'video' ? <Film className="h-4 w-4" /> : <FileText className="h-4 w-4" />}
              </span>
            )}
          </a>
        )
      })}
    </div>
  )
}
