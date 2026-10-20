"use client"

import { useState, type FormEvent } from "react"
import { useStore } from "@/lib/store"
import { PageHeader, RequireRol } from "@/components/trekk/page-header"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"

export default function PerfilPage() {
  return (
    <RequireRol roles={["cliente"]} mensaje="Iniciá sesión con tu cuenta de cliente para ver y modificar tus datos.">
      <PerfilForm />
    </RequireRol>
  )
}

function PerfilForm() {
  const { cliente, actualizarCliente } = useStore()
  const [form, setForm] = useState({ nombre: cliente.nombre, apellido: cliente.apellido, telefono: cliente.telefono, email: cliente.email })
  const [ok, setOk] = useState(false)

  function onSubmit(e: FormEvent) {
    e.preventDefault()
    actualizarCliente(form)
    setOk(true)
  }

  return (
    <main className="mx-auto max-w-2xl px-4 py-12">
      <PageHeader eyebrow="Cliente" title="Mis datos personales" description="Mantené actualizada tu información de contacto." />
      <form onSubmit={onSubmit} className="grid gap-4 rounded-2xl border bg-card p-6 sm:grid-cols-2">
        <div className="flex flex-col gap-2 sm:col-span-2">
          <Label htmlFor="dni">DNI</Label>
          <Input id="dni" value={cliente.dni} disabled />
        </div>
        {(
          [
            ["nombre", "Nombre"],
            ["apellido", "Apellido"],
            ["telefono", "Teléfono"],
            ["email", "Email"],
          ] as const
        ).map(([k, l]) => (
          <div key={k} className="flex flex-col gap-2">
            <Label htmlFor={k}>{l}</Label>
            <Input
              id={k}
              value={form[k]}
              onChange={(e) => {
                setOk(false)
                setForm({ ...form, [k]: e.target.value })
              }}
            />
          </div>
        ))}
        <div className="flex items-center justify-end gap-3 sm:col-span-2">
          {ok && (
            <p role="status" className="text-sm text-success">
              Datos actualizados
            </p>
          )}
          <Button type="submit">Guardar</Button>
        </div>
      </form>
    </main>
  )
}
