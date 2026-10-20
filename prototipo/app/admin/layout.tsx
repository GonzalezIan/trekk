"use client"

import type { ReactNode } from "react"
import { RequireRol } from "@/components/trekk/page-header"

export default function AdminLayout({ children }: { children: ReactNode }) {
  return (
    <RequireRol roles={["admin"]} mensaje="Esta sección es exclusiva para el personal administrativo.">
      <main className="mx-auto max-w-6xl px-4 py-12">{children}</main>
    </RequireRol>
  )
}
