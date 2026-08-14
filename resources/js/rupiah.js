const formatter = new Intl.NumberFormat('id-ID');

/** 25000 → "25.000" */
export const formatNumber = (value) => formatter.format(value ?? 0);

/** 25000 → "Rp 25.000", -2500 → "-Rp 2.500" (tanda minus di depan, bukan di antara). */
export const formatRupiah = (value) => {
    const amount = value ?? 0;

    return `${amount < 0 ? '-' : ''}Rp ${formatNumber(Math.abs(amount))}`;
};

const dayFormatter = new Intl.DateTimeFormat('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});

/** "2026-08-14" → "Jumat, 14 Agustus 2026" */
export const formatDate = (isoDate) => dayFormatter.format(new Date(`${isoDate}T00:00:00`));

/** Menyebut hari ini dan kemarin dengan namanya agar riwayat lebih mudah dibaca. */
export const formatDayLabel = (isoDate, todayIso) => {
    if (isoDate === todayIso) {
        return 'Hari ini';
    }

    const yesterday = new Date(`${todayIso}T00:00:00`);
    yesterday.setDate(yesterday.getDate() - 1);

    if (isoDate === yesterday.toISOString().slice(0, 10)) {
        return 'Kemarin';
    }

    return formatDate(isoDate);
};
