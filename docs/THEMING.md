# SunTrack UI Themes

Theme aktif disimpan di `localStorage` dengan key `suntrack_theme` dan disinkronkan ke preferensi user melalui `/api/v1/admin/me/preferences`.

## Membuat tampilan baru

Gunakan token semantik berikut agar komponen otomatis mengikuti light dan dark mode:

```html
<section class="border border-default bg-surface text-content">
    <h2 class="text-content">Judul</h2>
    <p class="text-content-soft">Deskripsi</p>
    <span class="text-content-muted">Metadata</span>
</section>
```

Token utama:

- `bg-page`: latar halaman.
- `bg-surface`: card, modal, sidebar, dan input.
- `bg-surface-muted`: area sekunder atau hover.
- `text-content`: teks utama.
- `text-content-soft`: teks sekunder.
- `text-content-muted`: placeholder dan metadata.
- `border-default`: border umum.
- `text-brand`, `bg-brand`, `bg-brand-soft`: warna brand adaptif.

Nilai warna dikelola terpusat di `resources/css/app.css`. Hindari warna netral hardcoded pada tampilan baru.
