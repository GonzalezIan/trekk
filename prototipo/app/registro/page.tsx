"use client"

import { Suspense, useState, type FormEvent } from "react"
import { useRouter, useSearchParams } from "next/navigation"
import { clienteDemo, clientesExtra } from "@/lib/data"
import { edad, useStore } from "@/lib/store"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"

const campos = [
  { id: "dni", label: "DNI", type: "text", auto: "off" },
  { id: "nombre", label: "Nombre", type: "text", auto: "given-name" },
  { id: "apellido", label: "Apellido", type: "text", auto: "family-name" },
  { id: "fechaNacimiento", label: "Fecha de nacimiento", type: "date", auto: "bday" },
  { id: "email", label: "Email", type: "email", auto: "email" },
  { id: "telefono", label: "Teléfono", type: "tel", auto: "tel" },
  { id: "usuario", label: "Nombre de usuario", type: "text", auto: "username" },
  { id: "clave", label: "Contraseña", type: "password", auto: "new-password" },
] as const

type Form = Record<(typeof campos)[number]["id"], string>

export default function RegistroPage() {
  return (
    <Suspense>
      <RegistroForm />
    </Suspense>
  )
}

function RegistroForm() {
  const router = useRouter()
  const nextParam = useSearchParams().get("next")
  const next = nextParam?.startsWith("/") && !nextParam.startsWith("//") ? nextParam : "/"
  const { iniciarSesion, actualizarCliente } = useStore()
  const [form, setForm] = useState<Form>(Object.fromEntries(campos.map((c) => [c.id, ""])) as Form)
  const [faltantes, setFaltantes] = useState<string[]>([])
  const [error, setError] = useState("")

  function onSubmit(e: FormEvent) {
    e.preventDefault()
    const vacios = campos.filter((c) => !form[c.id].trim()).map((c) => c.id)
    setFaltantes(vacios)
    const existentes = [clienteDemo, ...clientesExtra]
    if (vacios.length) return setError("Debe completar todos los campos obligatorios")
    if (edad(form.fechaNacimiento) < 18) return setError("Debe ser mayor de edad para registrarse como cliente")
    if (existentes.some((c) => c.dni === form.dni)) return setError("Ya existe un cliente registrado con ese DNI")
    if (existentes.some((c) => c.usuario === form.usuario))
      return setError("El nombre de usuario ingresado ya está en uso, elija otro")
    const { clave: _clave, ...datos } = form
    actualizarCliente(datos)
    iniciarSesion("cliente")
    router.push(next)
  }

  return (
    <main className="mx-auto flex max-w-2xl flex-col gap-6 px-4 py-16">
      <div className="flex flex-col gap-2">
        <h1 className="font-display text-3xl font-semibold">Crear cuenta de cliente</h1>
        <p className="text-muted-foreground">Es obligatorio ser mayor de 18 años. El DNI y el usuario deben ser únicos.</p>
      </div>
      <form onSubmit={onSubmit} noValidate className="grid gap-4 rounded-2xl border bg-card p-6 sm:grid-cols-2">
        {campos.map((c) => (
          <div key={c.id} className="flex flex-col gap-2">
            <Label htmlFor={c.id}>{c.label}</Label>
            <Input
              id={c.id}
              type={c.type}
              autoComplete={c.auto}
              aria-invalid={faltantes.includes(c.id) || undefined}
              value={form[c.id]}
              onChange={(e) => setForm({ ...form, [c.id]: e.target.value })}
            />
          </div>
        ))}
        {error && (
          <p role="alert" className="text-sm text-destructive sm:col-span-2">
            {error}
          </p>
        )}
        <div className="flex justify-end gap-2 sm:col-span-2">
          <Button type="button" variant="ghost" onClick={() => router.back()}>
            Cancelar
          </Button>
          <Button type="submit">Registrarme</Button>
        </div>
      </form>
    </main>
  )
}
