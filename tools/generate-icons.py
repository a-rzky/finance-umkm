#!/usr/bin/env python3
"""Membuat ikon PWA tanpa dependency eksternal.

Menggambar pada kanvas beresolusi tinggi lalu mengecilkannya dengan rata-rata
piksel, sehingga tepi lengkung tetap halus tanpa butuh library grafis.

Jalankan: python3 tools/generate-icons.py
"""

import os
import struct
import zlib

TEAL = (15, 118, 110)
WHITE = (255, 255, 255)

OUTPUT_DIR = os.path.join(os.path.dirname(__file__), '..', 'public', 'icons')

# Dirender sekali pada ukuran ini, lalu diturunkan ke ukuran yang dibutuhkan.
RENDER_SIZE = 1024


def write_png(path, size, pixels):
    """pixels: list baris, tiap baris list (r, g, b, a)."""
    raw = b''.join(
        b'\x00' + bytes(channel for px in row for channel in px) for row in pixels
    )

    def chunk(kind, data):
        return (
            struct.pack('>I', len(data))
            + kind
            + data
            + struct.pack('>I', zlib.crc32(kind + data) & 0xFFFFFFFF)
        )

    png = b'\x89PNG\r\n\x1a\n'
    png += chunk(b'IHDR', struct.pack('>IIBBBBB', size, size, 8, 6, 0, 0, 0))
    png += chunk(b'IDAT', zlib.compress(raw, 9))
    png += chunk(b'IEND', b'')

    with open(path, 'wb') as handle:
        handle.write(png)


def rounded_square(x, y, size, radius, inset):
    """True bila titik (x, y) berada di dalam persegi bersudut lengkung."""
    left = top = inset
    right = bottom = size - inset

    if not (left <= x < right and top <= y < bottom):
        return False

    # Hanya sudut yang perlu diuji jaraknya.
    cx = min(max(x, left + radius), right - radius)
    cy = min(max(y, top + radius), bottom - radius)

    return (x - cx) ** 2 + (y - cy) ** 2 <= radius**2


def render(size, bleed):
    """Menggambar ikon. bleed=True mengisi seluruh kanvas (untuk maskable).

    Ikon maskable wajib memenuhi seluruh kotak karena sistem operasi yang
    menentukan bentuk potongannya; sudut lengkung justru akan terpotong salah.
    """
    inset = 0 if bleed else size * 0.06
    radius = 0 if bleed else size * 0.22

    # Tiga batang menaik: lambang catatan yang bertambah tiap hari.
    unit = size / 100.0
    bar_width = 14 * unit
    gap = 8 * unit
    total_width = bar_width * 3 + gap * 2
    bar_left = (size - total_width) / 2
    baseline = size * 0.72
    heights = [22 * unit, 33 * unit, 44 * unit]

    bars = [
        (bar_left + index * (bar_width + gap), baseline - height, bar_width, height)
        for index, height in enumerate(heights)
    ]
    bar_radius = bar_width / 2

    rows = []
    for y in range(size):
        row = []
        for x in range(size):
            px, py = x + 0.5, y + 0.5

            if not rounded_square(px, py, size, radius, inset):
                row.append((0, 0, 0, 0))
                continue

            colour = TEAL
            for bx, by, bw, bh in bars:
                if bx <= px < bx + bw and by <= py < by + bh:
                    # Ujung atas batang dibulatkan agar tidak terlihat kaku.
                    cy = max(py, by + bar_radius)
                    cx = min(max(px, bx + bar_radius), bx + bw - bar_radius)
                    if (px - cx) ** 2 + (py - cy) ** 2 <= bar_radius**2 or py >= by + bar_radius:
                        colour = WHITE
                        break

            row.append((*colour, 255))
        rows.append(row)

    return rows


def downsample(pixels, source_size, target_size):
    """Rata-rata blok piksel — memberi efek anti-aliasing."""
    factor = source_size // target_size
    result = []

    for ty in range(target_size):
        row = []
        for tx in range(target_size):
            r = g = b = a = 0
            for dy in range(factor):
                for dx in range(factor):
                    pr, pg, pb, pa = pixels[ty * factor + dy][tx * factor + dx]
                    # Warna ditimbang alpha supaya tepi tidak berpendar gelap.
                    r += pr * pa
                    g += pg * pa
                    b += pb * pa
                    a += pa
            if a == 0:
                row.append((0, 0, 0, 0))
            else:
                count = factor * factor
                row.append((r // a, g // a, b // a, a // count))
        result.append(row)

    return result


def main():
    os.makedirs(OUTPUT_DIR, exist_ok=True)

    for bleed, sizes in ((False, (512, 192)), (True, (512,))):
        canvas = render(RENDER_SIZE, bleed)

        for size in sizes:
            name = f'icon-maskable-{size}.png' if bleed else f'icon-{size}.png'
            path = os.path.abspath(os.path.join(OUTPUT_DIR, name))
            write_png(path, size, downsample(canvas, RENDER_SIZE, size))
            print(f'dibuat: {path}')


if __name__ == '__main__':
    main()
