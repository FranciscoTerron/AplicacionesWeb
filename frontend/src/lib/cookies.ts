// Manejo de token en cookie (legible client-side; SSR-friendly).
// Nota (S-3): la cookie NO es HttpOnly porque el token se usa como Bearer en el
// header Authorization, y el front (SPA cross-domain) necesita leerlo desde JS.
// Un HttpOnly real exigiría auth por cookie cross-site (SameSite=None + cookies
// de terceros, bloqueadas por browsers) => rework frágil. Mitigación adoptada:
// TTL corto (7 días, igual al backend) + security headers/CSP (ver next.config).
const TOKEN_KEY = "ma_token";
const MAX_AGE = 60 * 60 * 24 * 7; // 7 días (igual al TTL del backend)

export function getToken(): string | null {
  if (typeof document === "undefined") return null;
  const match = document.cookie
    .split("; ")
    .find((row) => row.startsWith(`${TOKEN_KEY}=`));
  return match ? decodeURIComponent(match.split("=")[1]) : null;
}

// `Secure` solo en HTTPS (no en localhost http, o el browser ignora el set).
// SameSite=Lax + Secure es la config correcta para que la cookie sobreviva el
// ida-vuelta a un dominio externo (Mercado Pago) y no se pierda la sesión al
// volver atrás, sobre todo en mobile/Safari.
function secureFlag(): string {
  if (typeof location !== "undefined" && location.protocol === "https:") {
    return "; Secure";
  }
  return "";
}

export function setToken(token: string): void {
  if (typeof document === "undefined") return;
  document.cookie = `${TOKEN_KEY}=${encodeURIComponent(
    token
  )}; path=/; max-age=${MAX_AGE}; SameSite=Lax${secureFlag()}`;
}

export function clearToken(): void {
  if (typeof document === "undefined") return;
  document.cookie = `${TOKEN_KEY}=; path=/; max-age=0; SameSite=Lax${secureFlag()}`;
}
