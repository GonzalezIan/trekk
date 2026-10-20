import { RequireRol } from "@/components/trekk/page-header"
import { ExcursionDetalle } from "@/components/trekk/excursion-detalle"

export default async function ExcursionPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params
  return (
    <RequireRol roles={["cliente"]} mensaje="Las salidas programadas y los paquetes de cada excursión solo están disponibles para clientes registrados.">
      <ExcursionDetalle id={id} />
    </RequireRol>
  )
}
