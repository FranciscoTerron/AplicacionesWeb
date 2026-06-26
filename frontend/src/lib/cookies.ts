// Manejo de token en cookie (legible client-side; SSR-friendly)
const TOKEN_KEY = "ma_token";
const MAX_AGE = 60 * 60 * 24 * 30; // 30 días (igual al TTL del backend)

export function getToken(): string | null {
  if (typeof document === "undefined") return null;
  const match = document.cookie
    .split("; ")
    .find((row) => row.startsWith(`${TOKEN_KEY}=`));
  return match ? decodeURIComponent(match.split("=")[1]) : null;
}

export function setToken(token: string): void {
  if (typeof document === "undefined") return;
  document.cookie = `${TOKEN_KEY}=${encodeURIComponent(
    token
  )}; path=/; max-age=${MAX_AGE}; SameSite=Lax`;
}

export function clearToken(): void {
  if (typeof document === "undefined") return;
  document.cookie = `${TOKEN_KEY}=; path=/; max-age=0; SameSite=Lax`;
}
