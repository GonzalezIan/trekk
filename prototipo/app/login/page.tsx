import { Suspense } from "react"
import { LoginForm } from "@/components/trekk/login-form"

export default function LoginPage() {
  return (
    <main className="mx-auto flex max-w-md flex-col gap-6 px-4 py-16">
      <div className="flex flex-col gap-2">
        <h1 className="font-display text-3xl font-semibold">Iniciar sesión</h1>
        <p className="text-muted-foreground">Ingresá con tu usuario y contraseña.</p>
      </div>
      <Suspense>
        <LoginForm />
      </Suspense>
    </main>
  )
}
