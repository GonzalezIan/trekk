import { ReservaWizard } from "@/components/trekk/reserva-wizard"

export default async function ReservarPage({ params }: { params: Promise<{ paqueteId: string }> }) {
  const { paqueteId } = await params
  return <ReservaWizard paqueteId={paqueteId} />
}
