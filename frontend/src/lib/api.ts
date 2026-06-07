import { getToken, clearToken } from "./cookies";
import type { ApiResponse, PaginationMeta } from "@/types/api";

const BASE_URL =
  process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api/v1";

export class ApiError extends Error {
  status: number;
  constructor(message: string, status: number) {
    super(message);
    this.name = "ApiError";
    this.status = status;
  }
}

interface RequestOptions {
  method?: "GET" | "POST" | "PUT" | "DELETE";
  body?: unknown;
  // requiere token Bearer
  auth?: boolean;
  // override del token (útil en server o tras login inmediato)
  token?: string | null;
  // opciones de cache de Next para fetch (server components)
  cache?: RequestCache;
  next?: { revalidate?: number; tags?: string[] };
}

export interface ApiResult<T> {
  data: T;
  meta?: PaginationMeta;
  message?: string;
}

export async function apiFetch<T>(
  path: string,
  options: RequestOptions = {}
): Promise<ApiResult<T>> {
  const { method = "GET", body, auth = false, cache, next } = options;

  const headers: Record<string, string> = {
    Accept: "application/json",
  };
  if (body !== undefined) headers["Content-Type"] = "application/json";

  const token = options.token ?? (auth ? getToken() : null);
  if (token) headers["Authorization"] = `Bearer ${token}`;

  let res: Response;
  try {
    res = await fetch(`${BASE_URL}${path}`, {
      method,
      headers,
      body: body !== undefined ? JSON.stringify(body) : undefined,
      cache,
      next,
    });
  } catch {
    throw new ApiError("No se pudo conectar con el servidor", 0);
  }

  if (res.status === 401) {
    // token inválido/expirado → limpiar (la UI decide redirigir)
    clearToken();
    throw new ApiError("Sesión expirada. Iniciá sesión de nuevo.", 401);
  }

  let json: ApiResponse<T> | null = null;
  try {
    json = (await res.json()) as ApiResponse<T>;
  } catch {
    if (!res.ok) throw new ApiError(`Error ${res.status}`, res.status);
    throw new ApiError("Respuesta inválida del servidor", res.status);
  }

  if (!res.ok || json.success === false) {
    throw new ApiError(json?.message ?? `Error ${res.status}`, res.status);
  }

  return { data: json.data, meta: json.meta, message: json.message };
}

// Variante que devuelve el JSON completo sin desenvolver `data`.
// Necesaria para endpoints de auth, que ponen `token`/`user` al tope.
export async function apiFetchRaw<T>(
  path: string,
  options: RequestOptions = {}
): Promise<T> {
  const { method = "GET", body, auth = false, cache, next } = options;

  const headers: Record<string, string> = { Accept: "application/json" };
  if (body !== undefined) headers["Content-Type"] = "application/json";

  const token = options.token ?? (auth ? getToken() : null);
  if (token) headers["Authorization"] = `Bearer ${token}`;

  let res: Response;
  try {
    res = await fetch(`${BASE_URL}${path}`, {
      method,
      headers,
      body: body !== undefined ? JSON.stringify(body) : undefined,
      cache,
      next,
    });
  } catch {
    throw new ApiError("No se pudo conectar con el servidor", 0);
  }

  if (res.status === 401) {
    clearToken();
    throw new ApiError("Credenciales inválidas o sesión expirada.", 401);
  }

  let json: (T & { success?: boolean; message?: string }) | null = null;
  try {
    json = await res.json();
  } catch {
    throw new ApiError(`Error ${res.status}`, res.status);
  }

  if (!res.ok || json?.success === false) {
    throw new ApiError(json?.message ?? `Error ${res.status}`, res.status);
  }

  return json as T;
}
