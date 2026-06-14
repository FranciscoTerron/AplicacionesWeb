"use client";

import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useState,
} from "react";
import { useRouter } from "next/navigation";
import { getToken, setToken, clearToken } from "@/lib/cookies";
import * as api from "@/lib/endpoints";
import type { RegisterBody, User } from "@/types/api";

const USER_KEY = "ma_user";

interface AuthContextValue {
  user: User | null;
  loading: boolean;
  isAuthenticated: boolean;
  login: (email: string, password: string) => Promise<void>;
  register: (body: RegisterBody) => Promise<void>;
  logout: () => Promise<void>;
}

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const router = useRouter();
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  // Restaurar sesión al montar: token en cookie + user en localStorage.
  // Si hay user pero NO token (cookie vencida/borrada), descartar el user para
  // no quedar "pegado" logueado con un token inexistente.
  useEffect(() => {
    const token = getToken();
    const stored = localStorage.getItem(USER_KEY);
    if (token && stored) {
      try {
        setUser(JSON.parse(stored) as User);
      } catch {
        localStorage.removeItem(USER_KEY);
      }
    } else if (stored) {
      localStorage.removeItem(USER_KEY);
    }
    setLoading(false);
  }, []);

  // Recuperación ante 401: api.ts emite "auth:expired" cuando un recurso
  // protegido devuelve 401. Cerramos la sesión completa y vamos a login.
  useEffect(() => {
    function onExpired() {
      clearToken();
      localStorage.removeItem(USER_KEY);
      setUser(null);
      router.push("/login");
    }
    window.addEventListener("auth:expired", onExpired);
    return () => window.removeEventListener("auth:expired", onExpired);
  }, [router]);

  const persist = useCallback((token: string, u: User) => {
    setToken(token);
    localStorage.setItem(USER_KEY, JSON.stringify(u));
    setUser(u);
  }, []);

  const login = useCallback(
    async (email: string, password: string) => {
      const res = await api.login(email, password);
      persist(res.token, res.user);
    },
    [persist]
  );

  const register = useCallback(
    async (body: RegisterBody) => {
      const res = await api.register(body);
      persist(res.token, res.user);
    },
    [persist]
  );

  const logout = useCallback(async () => {
    try {
      await api.logout();
    } catch {
      // ignorar error de red al cerrar sesión
    }
    clearToken();
    localStorage.removeItem(USER_KEY);
    setUser(null);
  }, []);

  return (
    <AuthContext.Provider
      value={{
        user,
        loading,
        isAuthenticated: !!user,
        login,
        register,
        logout,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error("useAuth debe usarse dentro de <AuthProvider>");
  return ctx;
}
