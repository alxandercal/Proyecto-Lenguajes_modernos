// Contrato de datos entre el front y la API. Si la API cambia un campo, se cambia aquí.
export type Role = 'DONOR' | 'RECEIVER' | 'ADMIN'
export type DonationStatus = 'PUBLISHED' | 'RESERVED' | 'DELIVERED' | 'CANCELLED'
export type ReservationStatus = 'ACTIVE' | 'DELIVERED' | 'CANCELLED'
export type Unit = 'KG' | 'L' | 'PIEZA' | 'PORCION'

export interface ApiResponse<T> {
  data: T
}

export interface Profile {
  id: string
  uid: string
  email: string
  name: string
  role: Role
  organizationName: string
  zone: string
  createdAt: string
}

export interface HistoryEntry {
  at: string
  action: DonationStatus | 'EDITED' | 'RELEASED'
  byUid: string
  byName: string
  note: string
}

export interface PriorityFactor {
  key: 'urgency' | 'proximity' | 'volume'
  label: string
  points: number
  max: number
  detail: string
}

export interface Priority {
  score: number
  factors: PriorityFactor[]
}

export interface Donation {
  id: string
  ownerId: string
  donorName: string
  title: string
  category: string
  quantity: number
  unit: Unit
  quantityKg: number
  expiresAt: string
  pickupAddress: string
  zone: string
  notes: string
  status: DonationStatus
  reservedBy: string
  reservedByName: string
  history: HistoryEntry[]
  createdAt: string
  updatedAt: string
  priority?: Priority
}

// Lo que el donador captura en el formulario
export interface DonationInput {
  title: string
  category: string
  quantity: number
  unit: Unit
  expiresAt: string
  pickupAddress: string
  zone: string
  notes: string
}

export interface Reservation {
  id: string
  donationId: string
  donorId: string
  donorName: string
  receiverId: string
  receiverName: string
  donationTitle: string
  quantity: number
  unit: Unit
  quantityKg: number
  zone: string
  pickupAddress: string
  expiresAt: string
  status: ReservationStatus
  createdAt: string
  updatedAt: string
}

export interface Pickup {
  id: string
  donationId: string
  donorId: string
  donorName: string
  receiverId: string
  receiverName: string
  donationTitle: string
  category: string
  zone: string
  quantityKg: number
  receivedBy: string
  note: string
  evidenceUrl: string
  deliveredAt: string
}

export interface KgByGroup {
  value: string
  label: string
  kg: number
}

export interface Metrics {
  role: Role
  deliveries: number
  kgRecovered: number
  byCategory: KgByGroup[]
  statusCounts?: Record<DonationStatus, number>
  activeReservations?: number
  byZone?: KgByGroup[]
  topDonors?: { name: string; kg: number }[]
  recentPickups?: Pickup[]
}

export interface Option {
  value: string
  label: string
}

export interface Catalog {
  categories: Option[]
  units: (Option & { kg: number })[]
  zones: Option[]
  statuses: Option[]
}