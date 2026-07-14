import type { MetadataRoute } from "next";

// App Router genera y linkea /manifest.webmanifest automáticamente (HU-F09).
export default function manifest(): MetadataRoute.Manifest {
  return {
    name: "MA Piscinas",
    short_name: "MA Piscinas",
    description:
      "Tienda online de MA Piscinas: piscinas, accesorios y productos de mantenimiento.",
    start_url: "/",
    display: "standalone",
    background_color: "#ffffff",
    theme_color: "#0284c7",
    icons: [
      {
        src: "/icons/icon-192.png",
        sizes: "192x192",
        type: "image/png",
        purpose: "any",
      },
      {
        src: "/icons/icon-512.png",
        sizes: "512x512",
        type: "image/png",
        purpose: "any",
      },
      {
        src: "/icons/icon-maskable-512.png",
        sizes: "512x512",
        type: "image/png",
        purpose: "maskable",
      },
    ],
  };
}
