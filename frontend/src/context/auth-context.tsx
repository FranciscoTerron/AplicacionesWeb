"use client";

import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useState,
} from "react";
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
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  // Restaurar sesión al montar: token en cookie + user en localStorage
  useEffect(() => {
    const token = getToken();
    if (token) {
      const stored = localStorage.getItem(USER_KEY);
      if (stored) {
        try {
          setUser(JSON.parse(stored) as User);
        } catch {
          localStorage.removeItem(USER_KEY);
        }
      }
    }
    setLoading(false);
  }, []);

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
