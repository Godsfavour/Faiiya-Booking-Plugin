export interface Service {
  id: string;
  name: string;
  duration: number; // in minutes
  price: number;
  depositType: 'percentage' | 'fixed';
  depositValue: number;
  description: string;
  imageUrl?: string;
  assignedLocationIds?: string[]; // IDs of linked locations from categoryConfig.options (if empty/undefined, all locations apply)
}

export interface Booking {
  id: string;
  serviceId: string;
  customerName: string;
  customerEmail: string;
  customerPhone: string;
  customerAddress?: string;
  dateTime: string; // ISO string
  status: 'pending_payment' | 'confirmed' | 'completed' | 'cancelled' | 'no_show';
  paymentMethod: 'paystack' | 'bank_transfer';
  notes: string;
  depositPaid: number;
  balanceDue: number;
  referenceCode: string;
  createdAt: string;
  balancePaid: boolean;
  categoryOptionId?: string;
  categoryOptionName?: string;
  categoryPriceAdjustment?: number;
}

export interface PaystackConfig {
  enabled: boolean;
  publicKey: string;
  secretKey: string;
  testMode: boolean;
  validated: boolean;
}

export interface GoogleCalendarConfig {
  enabled: boolean;
  connected: boolean;
  email: string;
  validated: boolean;
}

export interface WhatsAppConfig {
  enabled: boolean;
  accessToken: string;
  phoneId: string;
  templateName: string;
  validated: boolean;
}

export interface TwilioConfig {
  enabled: boolean;
  accountSid: string;
  authToken: string;
  senderPhone: string;
  validated: boolean;
}

export interface IntegrationsState {
  paystack: PaystackConfig;
  googleCalendar: GoogleCalendarConfig;
  whatsapp: WhatsAppConfig;
  twilio: TwilioConfig;
}

export interface EmailTemplate {
  subject: string;
  body: string;
  enabled: boolean;
}

export interface EmailTemplates {
  booking_created_customer: EmailTemplate;
  booking_created_admin: EmailTemplate;
  booking_confirmed_customer: EmailTemplate;
  booking_confirmed_admin: EmailTemplate;
  booking_reminder_customer: EmailTemplate;
  booking_cancelled_customer: EmailTemplate;
  booking_cancelled_admin: EmailTemplate;
  booking_rescheduled_customer: EmailTemplate;
  balance_due_customer: EmailTemplate;
}

export interface NotificationLog {
  id: string;
  type: 'email' | 'whatsapp' | 'sms' | 'gcal' | 'paystack' | 'cron';
  title: string;
  content: string;
  recipient: string;
  timestamp: string;
  status: 'success' | 'failed';
}

export interface CategoryOption {
  id: string;
  name: string;
  priceValue: number;
}

export interface CategoryConfig {
  enabled: boolean;
  label: string;
  options: CategoryOption[];
}

export interface BusinessSettings {
  name: string;
  logoUrl: string;
  address: string;
  workingHoursStart: string; // "09:00"
  workingHoursEnd: string; // "17:00"
  timezone: string;
  brandColor: string;
  currency: string;
  categoryConfig: CategoryConfig;
  bankName?: string;
  accountNumber?: string;
  accountName?: string;
}
