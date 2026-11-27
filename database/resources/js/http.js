/**
 * Permintaan JSON ke Laravel dengan token CSRF.
 */
export async function sendJson(url, { method = 'POST', body = null } = {}) {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;

    const response = await fetch(url, {
        method,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: body ? JSON.stringify(body) : null,
    });

    let data = {};
    try {
        data = await response.json();
    } catch {
        data = {};
    }

    if (!response.ok) {
        const firstError = data.errors ? Object.values(data.errors)[0]?.[0] : null;
        const error = new Error(firstError || data.message || 'Terjadi kesalahan. Coba lagi.');
        error.status = response.status;
        error.data = data;
        throw error;
    }

    return data;
}

export const formatNumber = (value, decimals = 0) =>
    new Intl.NumberFormat('id-ID', { maximumFractionDigits: decimals, minimumFractionDigits: 0 }).format(Number(value) || 0);
