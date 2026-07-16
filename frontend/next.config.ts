import type { NextConfig } from "next";

// Security headers (refuerzan S-3: reducen la superficie de XSS que podría
// leer el token de la cookie). El token igual es de vida corta (7 días).
// Nota: no se fija una CSP estricta con nonces acá (Next inyecta scripts inline
// de hidratación); queda como follow-up. frame-ancestors 'none' + X-Frame-Options
// cubren clickjacking, que es lo más relevante para el panel del cliente.
const securityHeaders = [
  { key: "X-Content-Type-Options", value: "nosniff" },
  { key: "X-Frame-Options", value: "DENY" },
  { key: "Referrer-Policy", value: "strict-origin-when-cross-origin" },
  {
    key: "Strict-Transport-Security",
    value: "max-age=31536000; includeSubDomains",
  },
  {
    key: "Permissions-Policy",
    value: "camera=(), microphone=(), geolocation=()",
  },
];

const nextConfig: NextConfig = {
  turbopack: {
    root: import.meta.dirname,
  },
  images: {
    remotePatterns: [
      { protocol: "https", hostname: "res.cloudinary.com" },
      { protocol: "https", hostname: "images.unsplash.com" },
    ],
  },
  async headers() {
    return [{ source: "/:path*", headers: securityHeaders }];
  },
};

export default nextConfig;
