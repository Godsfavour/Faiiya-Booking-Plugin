import React from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { 
  Sparkles, Calendar, Settings, FileCode, CheckCircle, ListTodo, 
  HelpCircle, Monitor, Heart, Layers, ArrowRight, Activity, Download
} from 'lucide-react';
import { 
  Service, Booking, BusinessSettings, IntegrationsState, 
  EmailTemplates, NotificationLog 
} from './types';
import BookingFormSimulator from './components/BookingFormSimulator';
import WpAdminSimulator from './components/WpAdminSimulator';
import CodeExporter from './components/CodeExporter';
import LogsPanel from './components/LogsPanel';

// ==========================================
// SEED DATA & INITIALIZATION
// ==========================================

export const getCurrencySymbol = (code: string) => {
  const map: Record<string, string> = {
    NGN: '₦',
    USD: '$',
    EUR: '€',
    GBP: '£',
    GHS: '₵',
    ZAR: 'R',
    KES: 'KSh'
  };
  return map[code] || '$';
};

const INITIAL_SERVICES: Service[] = [
  {
    id: 'hair-styling',
    name: 'Premium Hair Styling',
    duration: 60,
    price: 30000.00,
    depositType: 'percentage',
    depositValue: 50, // 50%
    description: 'Top-tier cuts, customized styling, shampoo washing, blow-drying, and deep nourishing hydration treatments.',
    imageUrl: 'https://images.unsplash.com/photo-1560869713-7d0a29430803?w=500&auto=format&fit=crop&q=80'
  },
  {
    id: 'spa-massage',
    name: 'Aromatherapy Massage',
    duration: 90,
    price: 45000.00,
    depositType: 'fixed',
    depositValue: 15000.00, // 15,000 NGN fixed deposit
    description: 'Deep-tissue muscle relaxation session utilizing organic essential oils, hot stone heat packs, and acupressure.',
    imageUrl: 'https://images.unsplash.com/photo-1540555700478-4be289fbecef?w=500&auto=format&fit=crop&q=80'
  },
  {
    id: 'makeup-consult',
    name: 'Bridal Makeup Consulting',
    duration: 45,
    price: 55000.00,
    depositType: 'percentage',
    depositValue: 0, // No deposit, pay full price
    description: 'Personalized trial matching skin tones, custom lash selections, contour planning, and bridal palette selection.',
    imageUrl: 'https://images.unsplash.com/photo-1487412720507-e7ab37603c6f?w=500&auto=format&fit=crop&q=80'
  }
];

const INITIAL_BUSINESS_SETTINGS: BusinessSettings = {
  name: 'Yasmine Booking Studio',
  logoUrl: 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?w=100&auto=format&fit=crop&q=60',
  address: 'Suite 12, Yasmine Plaza, Lagos, Nigeria',
  workingHoursStart: '09:00',
  workingHoursEnd: '17:00',
  timezone: 'Africa/Lagos',
  brandColor: '#4f46e5', // Indigo primary
  currency: 'NGN',
  categoryConfig: {
    enabled: true,
    label: 'Location',
    options: [
      { id: 'lekki', name: 'Lekki Studio', priceValue: 20000 },
      { id: 'ondo', name: 'Ondo Outpost', priceValue: 170000 },
      { id: 'mainland', name: 'Mainland Hub', priceValue: 10000 }
    ]
  },
  bankName: 'Access Bank',
  accountNumber: '1480029384',
  accountName: 'Yasmine Booking Studio Ltd'
};

const INITIAL_INTEGRATIONS: IntegrationsState = {
  paystack: {
    enabled: false,
    publicKey: '',
    secretKey: '',
    testMode: true,
    validated: false
  },
  googleCalendar: {
    enabled: false,
    connected: false,
    email: '',
    validated: false
  },
  whatsapp: {
    enabled: false,
    accessToken: '',
    phoneId: '',
    templateName: '',
    validated: false
  },
  twilio: {
    enabled: false,
    accountSid: '',
    authToken: '',
    senderPhone: '',
    validated: false
  }
};

const INITIAL_EMAIL_TEMPLATES: EmailTemplates = {
  booking_created_customer: {
    subject: 'Booking Received: Appointment {{service_name}} is Pending Payment',
    body: `
      <div style="font-family: sans-serif; padding: 25px; color: #1e293b; background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0; max-width: 600px; margin: auto;">
        <h2 style="color: #0d9488; margin-top: 0;">Appointment Scheduled!</h2>
        <p>Dear <strong>{{customer_name}}</strong>,</p>
        <p>We've received your scheduling request for <strong>{{service_name}}</strong>. Your unique booking code is: <strong style="font-family: monospace; font-size: 14px; background: #ccfbf1; color: #0f766e; padding: 2px 6px; rounded: 4px;">{{ref_code}}</strong>.</p>
        
        <div style="background: #ffffff; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; margin: 15px 0;">
          <p style="margin: 0 0 8px 0;">📅 <strong>Date:</strong> {{booking_date}}</p>
          <p style="margin: 0;">⏰ <strong>Time:</strong> {{booking_time}}</p>
        </div>

        <p>Please note that this slot is held as <strong>Pending Payment</strong>. To guarantee your spot, please complete your deposit of <strong>\${{deposit_amount}}</strong>.</p>
        
        <p style="font-size: 11px; color: #64748b;">If paying via manual wire, please include <strong>{{ref_code}}</strong> in your bank narrative.</p>
        <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;" />
        <p style="font-size: 12px; color: #64748b; margin-bottom: 0;">Kind regards,<br><strong>{{business_name}}</strong></p>
      </div>
    `,
    enabled: true
  },
  booking_created_admin: {
    subject: '🚨 New Appointment Received (Pending Payment) - {{customer_name}}',
    body: `
      <div style="font-family: sans-serif; padding: 20px; color: #334155; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; max-width: 500px;">
        <h3 style="color: #c2410c; margin-top: 0;">New Incoming Appointment Request</h3>
        <p>A new appointment has been created and is waiting for payment validation:</p>
        <ul style="padding-left: 20px;">
          <li><strong>Client:</strong> {{customer_name}} ({{customer_phone}})</li>
          <li><strong>Service:</strong> {{service_name}}</li>
          <li><strong>Schedule:</strong> {{booking_date}} at {{booking_time}}</li>
          <li><strong>Deposit:</strong> \${{deposit_amount}}</li>
        </ul>
        <p>Verify bank narratives for code: <strong>{{ref_code}}</strong> and confirm the booking in your WordPress dashboard.</p>
      </div>
    `,
    enabled: true
  },
  booking_confirmed_customer: {
    subject: '🎉 Appointment Confirmed! See You on {{booking_date}}',
    body: `
      <div style="font-family: sans-serif; padding: 25px; color: #1e293b; background: #f0fdf4; border-radius: 12px; border: 1px solid #bbf7d0; max-width: 600px; margin: auto;">
        <h2 style="color: #16a34a; margin-top: 0;">Your Appointment is Confirmed!</h2>
        <p>Hi <strong>{{customer_name}}</strong>,</p>
        <p>We have successfully verified your deposit of <strong>\${{deposit_amount}}</strong>. Your session for <strong>{{service_name}}</strong> is now locked-in!</p>
        
        <div style="background: #ffffff; padding: 15px; border-radius: 8px; border: 1px solid #bbf7d0; margin: 15px 0;">
          <p style="margin: 0 0 8px 0;">📅 <strong>Date:</strong> {{booking_date}}</p>
          <p style="margin: 0 0 8px 0;">⏰ <strong>Time:</strong> {{booking_time}}</p>
          <p style="margin: 0;">💰 <strong>Remaining Balance Due on Site:</strong> \${{balance_due}}</p>
        </div>

        <p>Need to adjust your plans? You can manage your booking using the links below:</p>
        <p>
          <a href="{{reschedule_link}}" style="display: inline-block; padding: 8px 16px; background: #16a34a; color: #fff; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 12px; margin-right: 10px;">Reschedule Session</a>
          <a href="{{cancel_link}}" style="display: inline-block; padding: 8px 16px; background: #ef4444; color: #fff; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 12px;">Cancel Booking</a>
        </p>
        <hr style="border: 0; border-top: 1px solid #bbf7d0; margin: 20px 0;" />
        <p style="font-size: 12px; color: #16a34a; margin-bottom: 0;">Thank you,<br><strong>{{business_name}}</strong></p>
      </div>
    `,
    enabled: true
  },
  booking_confirmed_admin: {
    subject: '✅ Booking Confirmed: Slot Taken for {{service_name}}',
    body: `
      <div style="font-family: sans-serif; padding: 20px; color: #334155; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; max-width: 500px;">
        <h3 style="color: #16a34a; margin-top: 0;">Booking Confirmed & Synchronized</h3>
        <p>Deposit has been paid and appointment is officially active:</p>
        <ul style="padding-left: 20px;">
          <li><strong>Customer Name:</strong> {{customer_name}}</li>
          <li><strong>Service Name:</strong> {{service_name}}</li>
          <li><strong>Date / Time:</strong> {{booking_date}} at {{booking_time}}</li>
          <li><strong>Reference:</strong> {{ref_code}}</li>
        </ul>
        <p>The event has been dispatched to connected modules (Calendar, WhatsApp, Twilio) automatically.</p>
      </div>
    `,
    enabled: true
  },
  booking_reminder_customer: {
    subject: '⏰ Reminder: You have an Appointment tomorrow with {{business_name}}',
    body: `
      <div style="font-family: sans-serif; padding: 25px; color: #1e293b; background: #fef3c7; border-radius: 12px; border: 1px solid #fde68a; max-width: 600px; margin: auto;">
        <h2 style="color: #d97706; margin-top: 0;">Appointment Reminder!</h2>
        <p>Hi <strong>{{customer_name}}</strong>,</p>
        <p>This is a quick friendly reminder that your appointment for <strong>{{service_name}}</strong> is scheduled for tomorrow.</p>
        
        <div style="background: #ffffff; padding: 15px; border-radius: 8px; border: 1px solid #fde68a; margin: 15px 0;">
          <p style="margin: 0 0 8px 0;">📅 <strong>Date:</strong> {{booking_date}}</p>
          <p style="margin: 0 0 8px 0;">⏰ <strong>Time:</strong> {{booking_time}}</p>
          <p style="margin: 0;">💰 <strong>Remaining Balance Due:</strong> \${{balance_due}}</p>
        </div>

        <p>We look forward to seeing you! If you have any questions, please contact our team.</p>
        <hr style="border: 0; border-top: 1px solid #fde68a; margin: 20px 0;" />
        <p style="font-size: 12px; color: #b45309; margin-bottom: 0;">See you soon,<br><strong>{{business_name}}</strong></p>
      </div>
    `,
    enabled: true
  },
  booking_cancelled_customer: {
    subject: '🛑 Appointment Cancelled - Yasmine Booking Alert',
    body: `
      <div style="font-family: sans-serif; padding: 25px; color: #1e293b; background: #fef2f2; border-radius: 12px; border: 1px solid #fecaca; max-width: 600px; margin: auto;">
        <h2 style="color: #dc2626; margin-top: 0;">Appointment Cancelled</h2>
        <p>Hello <strong>{{customer_name}}</strong>,</p>
        <p>This email confirms that your appointment for <strong>{{service_name}}</strong> on <strong>{{booking_date}}</strong> has been **CANCELLED**.</p>
        <p>Your deposit has been credited to your booking profile. If this was an accident or you would like to reschedule a new session, please visit our homepage.</p>
        <hr style="border: 0; border-top: 1px solid #fecaca; margin: 20px 0;" />
        <p style="font-size: 12px; color: #991b1b; margin-bottom: 0;">Best regards,<br><strong>{{business_name}}</strong></p>
      </div>
    `,
    enabled: true
  },
  booking_cancelled_admin: {
    subject: '🚨 Appointment Cancelled Notice - {{customer_name}}',
    body: `
      <div style="font-family: sans-serif; padding: 20px; color: #334155; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; max-width: 500px;">
        <h3 style="color: #dc2626; margin-top: 0;">Appointment Cancelled Notification</h3>
        <p>An active appointment has been cancelled. Details of the slot released:</p>
        <ul style="padding-left: 20px;">
          <li><strong>Client Name:</strong> {{customer_name}} ({{customer_phone}})</li>
          <li><strong>Service Name:</strong> {{service_name}}</li>
          <li><strong>Date / Time:</strong> {{booking_date}} at {{booking_time}}</li>
          <li><strong>Booking Code:</strong> {{ref_code}}</li>
        </ul>
        <p>The slot has been released back to the general booking calendar.</p>
      </div>
    `,
    enabled: true
  },
  booking_rescheduled_customer: {
    subject: '🔄 Appointment Rescheduled: New Time Slot Locked-in',
    body: `
      <div style="font-family: sans-serif; padding: 25px; color: #1e293b; background: #eff6ff; border-radius: 12px; border: 1px solid #bfdbfe; max-width: 600px; margin: auto;">
        <h2 style="color: #2563eb; margin-top: 0;">Reschedule Receipt</h2>
        <p>Hello <strong>{{customer_name}}</strong>,</p>
        <p>Your appointment has been successfully updated to a new time slot.</p>
        
        <div style="background: #ffffff; padding: 15px; border-radius: 8px; border: 1px solid #bfdbfe; margin: 15px 0;">
          <p style="margin: 0 0 8px 0;">📅 <strong>New Date:</strong> {{booking_date}}</p>
          <p style="margin: 0 0 8px 0;">⏰ <strong>New Time:</strong> {{booking_time}}</p>
          <p style="margin: 0;">💰 <strong>Remaining Balance Due on Site:</strong> \${{balance_due}}</p>
        </div>

        <p>We look forward to seeing you at your updated slot!</p>
        <hr style="border: 0; border-top: 1px solid #bfdbfe; margin: 20px 0;" />
        <p style="font-size: 12px; color: #1e3a8a; margin-bottom: 0;">Warm regards,<br><strong>{{business_name}}</strong></p>
      </div>
    `,
    enabled: true
  },
  balance_due_customer: {
    subject: '🧾 Balance Due Receipt: Thank you for your visit!',
    body: `
      <div style="font-family: sans-serif; padding: 25px; color: #1e293b; background: #fafaf9; border-radius: 12px; border: 1px solid #e7e5e4; max-width: 600px; margin: auto;">
        <h2 style="color: #78350f; margin-top: 0;">Appointment Receipt</h2>
        <p>Hi <strong>{{customer_name}}</strong>,</p>
        <p>Thank you for visiting us for your session of <strong>{{service_name}}</strong> today! We hope you loved the experience.</p>
        
        <div style="background: #ffffff; padding: 15px; border-radius: 8px; border: 1px solid #e7e5e4; margin: 15px 0;">
          <p style="margin: 0 0 8px 0;">💰 <strong>Total Price:</strong> \${{booking_date}} (Paid)</p>
          <p style="margin: 0 0 8px 0;">💳 <strong>Deposit Paid Ahead:</strong> \${{deposit_amount}}</p>
          <p style="margin: 0; font-weight: bold; color: #059669;">✅ Remaining Balance Cleared: \${{balance_due}}</p>
        </div>

        <p>If you'd like to share feedback or reserve another appointment, our doors are always open.</p>
        <hr style="border: 0; border-top: 1px solid #e7e5e4; margin: 20px 0;" />
        <p style="font-size: 12px; color: #78350f; margin-bottom: 0;">Warmest regards,<br><strong>{{business_name}}</strong></p>
      </div>
    `,
    enabled: true
  }
};

const SEED_BOOKINGS: Booking[] = [
  {
    id: 'yb_74d28',
    serviceId: 'spa-massage',
    customerName: 'Aisha Bello',
    customerEmail: 'aisha.bello@example.com',
    customerPhone: '+2348035552093',
    dateTime: `${new Date().toISOString().split('T')[0]}T10:00:00`,
    status: 'confirmed',
    paymentMethod: 'bank_transfer',
    notes: 'Please ensure essential oils are strictly nut-free.',
    depositPaid: 50.00,
    balanceDue: 130.00,
    referenceCode: 'YBK-A8B9',
    createdAt: new Date(Date.now() - 3600000 * 24).toISOString(),
    balancePaid: false
  },
  {
    id: 'yb_12f90',
    serviceId: 'hair-styling',
    customerName: 'Chidi Okafor',
    customerEmail: 'chidi.okafor@example.com',
    customerPhone: '+2348021234567',
    dateTime: `${new Date().toISOString().split('T')[0]}T14:00:00`,
    status: 'pending_payment',
    paymentMethod: 'bank_transfer',
    notes: 'Need a style touch-up for an evening red carpet.',
    depositPaid: 60.00,
    balanceDue: 60.00,
    referenceCode: 'YBK-F43A',
    createdAt: new Date().toISOString(),
    balancePaid: false
  }
];

const SEED_LOGS: NotificationLog[] = [
  {
    id: 'l_1',
    type: 'cron',
    title: 'WP_Cron Setup',
    content: 'Hourly Cron hook yasmine_booking_cron_reminders registered successfully on activation.\nTwiceDaily Cron hook yasmine_booking_cron_cleanup registered successfully on activation.',
    recipient: 'WordPress ActionScheduler',
    timestamp: new Date(Date.now() - 120000).toISOString(),
    status: 'success'
  },
  {
    id: 'l_2',
    type: 'cron',
    title: 'Database Schema Sync',
    content: 'Table wp_yasmine_bookings created successfully using dbDelta().\nTable wp_yasmine_services created successfully using dbDelta().\nTable wp_yasmine_payments created successfully.\nTable wp_yasmine_logs created successfully.',
    recipient: 'WordPress Core DBEngine',
    timestamp: new Date(Date.now() - 110000).toISOString(),
    status: 'success'
  }
];

export default function App() {
  const [activePane, setActivePane] = React.useState<'simulator' | 'admin' | 'code'>('simulator');
  
  // App States
  const [services, setServices] = React.useState<Service[]>(INITIAL_SERVICES);
  const [bookings, setBookings] = React.useState<Booking[]>(SEED_BOOKINGS);
  const [businessSettings, setBusinessSettings] = React.useState<BusinessSettings>(INITIAL_BUSINESS_SETTINGS);
  const [integrations, setIntegrations] = React.useState<IntegrationsState>(INITIAL_INTEGRATIONS);
  const [emailTemplates, setEmailTemplates] = React.useState<EmailTemplates>(INITIAL_EMAIL_TEMPLATES);
  const [logs, setLogs] = React.useState<NotificationLog[]>(SEED_LOGS);

  // Global Toast State
  const [toast, setToast] = React.useState<{ id: number; message: string; type: 'success' | 'info' | 'warning' } | null>(null);

  const showToast = React.useCallback((message: string, type: 'success' | 'info' | 'warning' = 'success') => {
    const id = Date.now();
    setToast({ id, message, type });
    setTimeout(() => {
      setToast(prev => (prev?.id === id ? null : prev));
    }, 3500);
  }, []);

  // Helper: append logs
  const addLog = (type: NotificationLog['type'], title: string, content: string, recipient: string, status: 'success' | 'failed' = 'success') => {
    const newLog: NotificationLog = {
      id: 'l_' + Math.random().toString(36).substring(2, 9),
      type,
      title,
      content,
      recipient,
      timestamp: new Date().toISOString(),
      status
    };
    setLogs(prev => [newLog, ...prev]);
  };

  const handleClearLogs = () => {
    setLogs([]);
  };

  // Helper: parse email template
  const parseTemplate = (body: string, variables: Record<string, string>) => {
    let parsed = body;
    Object.entries(variables).forEach(([key, val]) => {
      parsed = parsed.replace(new RegExp(`{{${key}}}`, 'g'), val);
    });
    return parsed;
  };

  // ==========================================
  // CORE BOOKING DISPATCHER HANDLERS
  // ==========================================

  // Customer creates a booking from Front-End Widget
  const handleCreateBookingSubmit = async (bookingData: Omit<Booking, 'id' | 'createdAt' | 'status' | 'referenceCode' | 'depositPaid' | 'balanceDue' | 'balancePaid'>) => {
    const service = services.find(s => s.id === bookingData.serviceId) || services[0];
    
    // Calculate category pricing adjustment
    const categoryOption = businessSettings.categoryConfig?.options?.find(opt => opt.id === bookingData.categoryOptionId);
    const categoryPrice = (businessSettings.categoryConfig?.enabled && categoryOption) ? categoryOption.priceValue : 0;
    const price = service.price + categoryPrice;
    
    // Calculate deposit and balance
    let deposit = 0;
    if (service.depositValue > 0) {
      if (service.depositType === 'percentage') {
        deposit = (price * service.depositValue) / 100;
      } else {
        deposit = service.depositValue;
      }
    }
    const balance = price - deposit;
    const bookingId = 'yb_' + Math.random().toString(36).substring(2, 7);
    const referenceCode = 'YBK-' + Math.random().toString(36).substring(2, 6).toUpperCase();
    
    // status is pending_payment initially
    const status: Booking['status'] = 'pending_payment';

    const newBooking: Booking = {
      id: bookingId,
      ...bookingData,
      status,
      depositPaid: deposit,
      balanceDue: balance,
      referenceCode,
      createdAt: new Date().toISOString(),
      balancePaid: false,
      categoryOptionId: bookingData.categoryOptionId,
      categoryOptionName: categoryOption?.name,
      categoryPriceAdjustment: categoryPrice,
    };

    setBookings(prev => [newBooking, ...prev]);

    // Dispatch logs
    const symbol = getCurrencySymbol(businessSettings.currency);
    const formattedDate = new Date(bookingData.dateTime).toLocaleDateString([], { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
    const formattedTime = new Date(bookingData.dateTime).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    const categoryText = categoryOption ? ` [${businessSettings.categoryConfig.label}: ${categoryOption.name} (+${symbol}${categoryPrice.toLocaleString()})]` : '';
    const fullServiceName = service.name + categoryText;

    const templateVars = {
      customer_name: bookingData.customerName,
      service_name: fullServiceName,
      booking_date: formattedDate,
      booking_time: formattedTime,
      deposit_amount: `${symbol}${deposit.toLocaleString()}`,
      balance_due: `${symbol}${balance.toLocaleString()}`,
      business_name: businessSettings.name,
      reschedule_link: `https://yourdomain.com/booking-reschedule/?id=${bookingId}`,
      cancel_link: `https://yourdomain.com/booking-cancel/?id=${bookingId}`,
      logo_url: businessSettings.logoUrl,
      ref_code: referenceCode,
    };

    const adminEmailAddress = 'admin@' + businessSettings.name.toLowerCase().replace(/\s+/g, '') + '.com';

    // 1. Send Email Created Alert to Customer
    const customerMail = emailTemplates.booking_created_customer;
    if (customerMail.enabled) {
      const parsedBody = parseTemplate(customerMail.body, templateVars);
      addLog('email', 'Email Dispatched to Customer', parsedBody, bookingData.customerEmail);
    }

    // 2. Send Email Created Alert to Admin
    const adminMail = emailTemplates.booking_created_admin;
    if (adminMail.enabled) {
      const parsedBody = parseTemplate(adminMail.body, templateVars);
      addLog('email', 'Email Dispatched to Admin', parsedBody, adminEmailAddress);
    }

    // 3. SMS Alert (if Twilio is enabled)
    if (integrations.twilio.enabled) {
      const text = `Hi ${bookingData.customerName}, your booking of ${fullServiceName} is scheduled on ${formattedDate} at ${formattedTime}. Deposit due: ${symbol}${deposit.toLocaleString()}. Code: ${referenceCode}.`;
      addLog('sms', 'Twilio SMS Dispatched', text, bookingData.customerPhone);
    }

    // 4. WhatsApp Alert (if WhatsApp is enabled)
    if (integrations.whatsapp.enabled) {
      const whatsappParams = [bookingData.customerName, fullServiceName, formattedDate, formattedTime];
      addLog('whatsapp', `WhatsApp Message Queue: ${integrations.whatsapp.templateName}`, `Template parameters mapped: [${whatsappParams.join(', ')}]`, bookingData.customerPhone);
    }

    // If Paid via Paystack, simulate instant transaction hook callback confirmation
    if (bookingData.paymentMethod === 'paystack') {
      addLog('paystack', 'Paystack Gateway Initialized', `Initialized transaction of ${symbol}${deposit.toLocaleString()} for client ${bookingData.customerEmail}. Reference: ${referenceCode}. Callback url: ${window.location.origin}/paystack-callback`, bookingData.customerEmail);
      
      // Auto-reconcile for checkout success simulation
      await new Promise(resolve => setTimeout(resolve, 500));
      addLog('paystack', 'Paystack Webhook Verified Success', `Successfully received deposit payment of ${symbol}${deposit.toLocaleString()} from Paystack Webhook. Reference code matching confirmed.`, 'Paystack API Server');
      
      // Upgrade status to confirmed
      setBookings(prev => prev.map(b => b.id === bookingId ? { ...b, status: 'confirmed' } : b));

      // Trigger confirm logs
      const confirmedCustomerMail = emailTemplates.booking_confirmed_customer;
      if (confirmedCustomerMail.enabled) {
        const parsedBody = parseTemplate(confirmedCustomerMail.body, templateVars);
        addLog('email', 'Payment Confirmed Email Sent to Customer', parsedBody, bookingData.customerEmail);
      }

      const confirmedAdminMail = emailTemplates.booking_confirmed_admin;
      if (confirmedAdminMail.enabled) {
        const parsedBody = parseTemplate(confirmedAdminMail.body, templateVars);
        addLog('email', 'Admin Confirmation Email Sent', parsedBody, adminEmailAddress);
      }

      // If Google Calendar enabled: Sync event!
      if (integrations.googleCalendar.enabled) {
        addLog('gcal', 'Google Calendar Event Created', `Created Event: "${fullServiceName} - ${bookingData.customerName}"\nDescription: Customer Phone: ${bookingData.customerPhone}\nSchedule: ${formattedDate} at ${formattedTime}\nZone: ${businessSettings.timezone}`, integrations.googleCalendar.email);
      }

      return { id: bookingId, referenceCode, depositPaid: deposit, balanceDue: balance, status: 'confirmed' as const };
    }

    return { id: bookingId, referenceCode, depositPaid: deposit, balanceDue: balance, status: 'pending_payment' as const };
  };

  // Admin manually confirms a Bank Wire payment
  const handleConfirmBankTransfer = (bookingId: string) => {
    const booking = bookings.find(b => b.id === bookingId);
    if (!booking) return;

    setBookings(prev => prev.map(b => b.id === bookingId ? { ...b, status: 'confirmed' } : b));
    
    const symbol = getCurrencySymbol(businessSettings.currency);
    addLog('paystack', 'Manual Wire Reconciled', `Admin manually verified bank receipt of ${symbol}${booking.depositPaid.toLocaleString()} for code: ${booking.referenceCode}. Booking status set to Confirmed.`, 'Admin Manual Action');

    // Trigger confirmation alerts
    const service = services.find(s => s.id === booking.serviceId) || services[0];
    const formattedDate = new Date(booking.dateTime).toLocaleDateString([], { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
    const formattedTime = new Date(booking.dateTime).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    const categoryText = booking.categoryOptionName ? ` [${businessSettings.categoryConfig.label}: ${booking.categoryOptionName} (+${symbol}${booking.categoryPriceAdjustment?.toLocaleString()})]` : '';
    const fullServiceName = service.name + categoryText;

    const templateVars = {
      customer_name: booking.customerName,
      service_name: fullServiceName,
      booking_date: formattedDate,
      booking_time: formattedTime,
      deposit_amount: `${symbol}${booking.depositPaid.toLocaleString()}`,
      balance_due: `${symbol}${booking.balanceDue.toLocaleString()}`,
      business_name: businessSettings.name,
      reschedule_link: `https://yourdomain.com/booking-reschedule/?id=${bookingId}`,
      cancel_link: `https://yourdomain.com/booking-cancel/?id=${bookingId}`,
      logo_url: businessSettings.logoUrl,
      ref_code: booking.referenceCode,
    };

    const adminEmailAddress = 'admin@' + businessSettings.name.toLowerCase().replace(/\s+/g, '') + '.com';

    // 1. Send Invoice Confirmed Alert to Customer
    const confirmedCustomerMail = emailTemplates.booking_confirmed_customer;
    if (confirmedCustomerMail.enabled) {
      const parsedBody = parseTemplate(confirmedCustomerMail.body, templateVars);
      addLog('email', 'Deposit Confirmed Email Sent to Customer', parsedBody, booking.customerEmail);
    }

    // 2. Send Verified Alert to Admin (Relevant Bodies)
    const confirmedAdminMail = emailTemplates.booking_confirmed_admin;
    if (confirmedAdminMail && confirmedAdminMail.enabled) {
      const parsedBody = parseTemplate(confirmedAdminMail.body, templateVars);
      addLog('email', 'Booking Verified Email Sent to Admin', parsedBody, adminEmailAddress);
    }

    // 3. Google Calendar Sync if active
    if (integrations.googleCalendar.enabled) {
      addLog('gcal', 'Google Calendar Event Synced', `Created Event: "${fullServiceName} - ${booking.customerName}"\nDuration: ${service.duration} mins\nSchedule: ${formattedDate} at ${formattedTime}`, integrations.googleCalendar.email);
    }

    // 4. SMS/WhatsApp alerts if active
    if (integrations.twilio.enabled) {
      addLog('sms', 'Twilio SMS Dispatched (Confirmed)', `Hi ${booking.customerName}, payment verified! Your session for ${fullServiceName} is CONFIRMED on ${formattedDate} at ${formattedTime}.`, booking.customerPhone);
    }
  };

  // Admin Reschedules booking
  const handleRescheduleBooking = (bookingId: string, newDateTime: string) => {
    const booking = bookings.find(b => b.id === bookingId);
    if (!booking) return;

    setBookings(prev => prev.map(b => b.id === bookingId ? { ...b, dateTime: newDateTime } : b));
    addLog('cron', 'Appointment Rescheduled', `Rescheduled booking ${bookingId} to ${newDateTime}. Notifications generated.`, 'Admin Dashboard');

    const service = services.find(s => s.id === booking.serviceId) || services[0];
    const formattedDate = new Date(newDateTime).toLocaleDateString([], { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
    const formattedTime = new Date(newDateTime).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    const symbol = getCurrencySymbol(businessSettings.currency);
    const categoryText = booking.categoryOptionName ? ` [${businessSettings.categoryConfig.label}: ${booking.categoryOptionName}]` : '';
    const fullServiceName = service.name + categoryText;

    const templateVars = {
      customer_name: booking.customerName,
      service_name: fullServiceName,
      booking_date: formattedDate,
      booking_time: formattedTime,
      deposit_amount: `${symbol}${booking.depositPaid.toLocaleString()}`,
      balance_due: `${symbol}${booking.balanceDue.toLocaleString()}`,
      business_name: businessSettings.name,
      reschedule_link: `https://yourdomain.com/booking-reschedule/?id=${bookingId}`,
      cancel_link: `https://yourdomain.com/booking-cancel/?id=${bookingId}`,
      logo_url: businessSettings.logoUrl,
      ref_code: booking.referenceCode,
    };

    // Client alert
    const rescheduleMail = emailTemplates.booking_rescheduled_customer;
    if (rescheduleMail.enabled) {
      const parsedBody = parseTemplate(rescheduleMail.body, templateVars);
      addLog('email', 'Reschedule Notice Dispatched to Customer', parsedBody, booking.customerEmail);
    }

    // GCal sync update
    if (integrations.googleCalendar.enabled) {
      addLog('gcal', 'Google Calendar Event Updated', `Rescheduled Event ID: ${bookingId}. Moved to ${formattedDate} at ${formattedTime}.`, integrations.googleCalendar.email);
    }
  };

  // Admin marks appointment completed -> requests remaining balance invoice
  const handleCompleteBooking = (bookingId: string) => {
    const booking = bookings.find(b => b.id === bookingId);
    if (!booking) return;

    setBookings(prev => prev.map(b => b.id === bookingId ? { ...b, status: 'completed', balancePaid: true } : b));
    addLog('cron', 'Booking Marked Completed', `Marked booking ID: ${bookingId} as Completed. Final balance billing dispatched.`, 'Admin Dashboard');

    const service = services.find(s => s.id === booking.serviceId) || services[0];
    const formattedDate = new Date(booking.dateTime).toLocaleDateString([], { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
    const formattedTime = new Date(booking.dateTime).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    const symbol = getCurrencySymbol(businessSettings.currency);
    const categoryText = booking.categoryOptionName ? ` [${businessSettings.categoryConfig.label}: ${booking.categoryOptionName}]` : '';
    const fullServiceName = service.name + categoryText;

    const totalCost = booking.depositPaid + booking.balanceDue;

    const templateVars = {
      customer_name: booking.customerName,
      service_name: fullServiceName,
      booking_date: formattedDate,
      booking_time: formattedTime,
      deposit_amount: `${symbol}${booking.depositPaid.toLocaleString()}`,
      balance_due: `${symbol}${booking.balanceDue.toLocaleString()}`,
      business_name: businessSettings.name,
      reschedule_link: '',
      cancel_link: '',
      logo_url: businessSettings.logoUrl,
      ref_code: booking.referenceCode,
    };

    const balanceMail = emailTemplates.balance_due_customer;
    if (balanceMail.enabled) {
      // replace total in body
      let bodyText = balanceMail.body.replace(/\$\{\{booking_date\}\}/g, `${symbol}${totalCost.toLocaleString()}`);
      const parsedBody = parseTemplate(bodyText, templateVars);
      addLog('email', 'Final Balance Cleared Receipt sent to Customer', parsedBody, booking.customerEmail);
    }
  };

  // Admin cancels booking
  const handleCancelBooking = (bookingId: string) => {
    const booking = bookings.find(b => b.id === bookingId);
    if (!booking) return;

    setBookings(prev => prev.map(b => b.id === bookingId ? { ...b, status: 'cancelled' } : b));
    addLog('cron', 'Booking Cancelled', `Cancelled scheduled appointment ID: ${bookingId}. Released slots and sent warnings.`, 'Admin Dashboard');

    const service = services.find(s => s.id === booking.serviceId) || services[0];
    const formattedDate = new Date(booking.dateTime).toLocaleDateString([], { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
    const formattedTime = new Date(booking.dateTime).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    const symbol = getCurrencySymbol(businessSettings.currency);
    const categoryText = booking.categoryOptionName ? ` [${businessSettings.categoryConfig.label}: ${booking.categoryOptionName}]` : '';
    const fullServiceName = service.name + categoryText;

    const templateVars = {
      customer_name: booking.customerName,
      service_name: fullServiceName,
      booking_date: formattedDate,
      booking_time: formattedTime,
      deposit_amount: `${symbol}${booking.depositPaid.toLocaleString()}`,
      balance_due: `${symbol}${booking.balanceDue.toLocaleString()}`,
      business_name: businessSettings.name,
      reschedule_link: '',
      cancel_link: '',
      logo_url: businessSettings.logoUrl,
      ref_code: booking.referenceCode,
    };

    const adminEmailAddress = 'admin@' + businessSettings.name.toLowerCase().replace(/\s+/g, '') + '.com';

    // 1. Send Cancellation Alert to Customer
    const cancelMail = emailTemplates.booking_cancelled_customer;
    if (cancelMail.enabled) {
      const parsedBody = parseTemplate(cancelMail.body, templateVars);
      addLog('email', 'Cancellation Invoice Dispatched to Customer', parsedBody, booking.customerEmail);
    }

    // 2. Send Cancellation Alert to Admin (Relevant Bodies)
    const cancelAdminMail = emailTemplates.booking_cancelled_admin;
    if (cancelAdminMail && cancelAdminMail.enabled) {
      const parsedBody = parseTemplate(cancelAdminMail.body, templateVars);
      addLog('email', 'Cancellation Notice Dispatched to Admin', parsedBody, adminEmailAddress);
    }

    if (integrations.googleCalendar.enabled) {
      addLog('gcal', 'Google Calendar Event Deleted', `Deleted synchronized Calendar event ID associated with booking ID: ${bookingId}. Slot released.`, integrations.googleCalendar.email);
    }
  };

  // Run WP Cron scheduler simulations
  const handleTriggerCron = (cronType: 'reminders' | 'cleanup') => {
    if (cronType === 'reminders') {
      const symbol = getCurrencySymbol(businessSettings.currency);
      addLog('cron', 'WP_Cron execution: yasmine_booking_cron_reminders', 'Starting hourly cron scan for upcoming scheduled sessions...', 'WP-Cron Controller');
      
      // Look for active confirmed bookings
      const confirmedBookings = bookings.filter(b => b.status === 'confirmed');
      if (confirmedBookings.length === 0) {
        addLog('cron', 'yasmine_booking_cron_reminders: Finished', 'Scan complete. 0 pending reminders matched.', 'WP-Cron Controller');
        return;
      }

      confirmedBookings.forEach(booking => {
        const service = services.find(s => s.id === booking.serviceId) || services[0];
        const formattedDate = new Date(booking.dateTime).toLocaleDateString([], { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
        const formattedTime = new Date(booking.dateTime).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        const symbol = getCurrencySymbol(businessSettings.currency);
        const categoryText = booking.categoryOptionName ? ` [${businessSettings.categoryConfig.label}: ${booking.categoryOptionName}]` : '';
        const fullServiceName = service.name + categoryText;

        const templateVars = {
          customer_name: booking.customerName,
          service_name: fullServiceName,
          booking_date: formattedDate,
          booking_time: formattedTime,
          deposit_amount: `${symbol}${booking.depositPaid.toLocaleString()}`,
          balance_due: `${symbol}${booking.balanceDue.toLocaleString()}`,
          business_name: businessSettings.name,
          reschedule_link: `https://yourdomain.com/booking-reschedule/?id=${booking.id}`,
          cancel_link: `https://yourdomain.com/booking-cancel/?id=${booking.id}`,
          logo_url: businessSettings.logoUrl,
          ref_code: booking.referenceCode,
        };

        const reminderMail = emailTemplates.booking_reminder_customer;
        if (reminderMail.enabled) {
          const parsedBody = parseTemplate(reminderMail.body, templateVars);
          addLog('email', `WP-Cron: Client Reminder Dispatched`, parsedBody, booking.customerEmail);
        }
      });

      addLog('cron', 'yasmine_booking_cron_reminders: Finished', `Scan complete. Dispatched ${confirmedBookings.length} email reminders.`, 'WP-Cron Controller');
    } else {
      addLog('cron', 'WP_Cron execution: yasmine_booking_cron_cleanup', 'Scanning database for unpaid overdue pending slots...', 'WP-Cron Controller');
      
      const pendingBookings = bookings.filter(b => b.status === 'pending_payment');
      let cancelledCount = 0;

      pendingBookings.forEach(booking => {
        const isPaystack = booking.paymentMethod === 'paystack';
        const createdAgeMs = Date.now() - new Date(booking.createdAt).getTime();
        
        // Auto-cancel Paystack pending over 30 mins (1800000ms), manual bank wires over 24 hours (86400000ms)
        const limit = isPaystack ? 1800000 : 86400000;
        
        if (createdAgeMs > limit) {
          setBookings(prev => prev.map(b => b.id === booking.id ? { ...b, status: 'cancelled' } : b));
          addLog('cron', 'Auto-Expired Pending Booking', `Overdue slot ID: ${booking.id} (${booking.customerName}) auto-cancelled. Reassigned status to Cancelled. Released slot.`, 'WP-Cron Controller');
          cancelledCount++;
        }
      });

      addLog('cron', 'yasmine_booking_cron_cleanup: Finished', `Database scan completed. Overdue pending slots auto-released: ${cancelledCount}.`, 'WP-Cron Controller');
    }
  };

  return (
    <div id="app-shell" className="min-h-screen bg-slate-50 text-slate-900 flex flex-col p-6 gap-6 selection:bg-indigo-500/10 selection:text-indigo-800">
      {/* Top Main Navigation Bar - Bento Styled Card */}
      <header id="top-navbar" className="bg-white border border-slate-200/60 shadow-xs px-6 py-4 rounded-3xl flex flex-col sm:flex-row sm:items-center sm:justify-between flex-shrink-0 gap-4 z-10">
        <div className="flex items-center space-x-3.5">
          <div className="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-teal-400 flex items-center justify-center shadow-xs text-white font-extrabold text-base tracking-wider">
            Y
          </div>
          <div>
            <div className="flex items-center space-x-2">
              <h1 className="text-sm font-extrabold tracking-tight text-slate-800 uppercase">Yasmine Booking</h1>
              <span className="bg-indigo-50 text-indigo-700 text-[10px] font-bold px-2 py-0.5 rounded-full border border-indigo-100">v1.2 Release</span>
            </div>
            <p className="text-[11px] text-slate-500 mt-0.5 font-medium">Modular WordPress Booking &amp; Notification Engine</p>
          </div>
        </div>

        {/* Navigation Selector Tabs - Bento Tab Bar */}
        <div id="top-nav-tabs" className="flex bg-slate-100/80 border border-slate-200/40 p-1 rounded-2xl">
          <motion.button
            whileTap={{ scale: 0.94 }}
            onClick={() => {
              setActivePane('simulator');
              showToast('Switched to Booking Widget Form', 'info');
            }}
            className={`flex items-center space-x-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold transition duration-150 cursor-pointer ${
              activePane === 'simulator' 
                ? 'bg-white text-indigo-600 shadow-sm border border-slate-200/40 font-bold' 
                : 'text-slate-500 hover:text-slate-800 hover:bg-white/40'
            }`}
          >
            <Monitor className="w-3.5 h-3.5" />
            <span>Booking Widget Form</span>
          </motion.button>

          <motion.button
            whileTap={{ scale: 0.94 }}
            onClick={() => {
              setActivePane('admin');
              showToast('Switched to WordPress Admin Dashboard', 'info');
            }}
            className={`flex items-center space-x-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold transition duration-150 cursor-pointer ${
              activePane === 'admin' 
                ? 'bg-white text-indigo-600 shadow-sm border border-slate-200/40 font-bold' 
                : 'text-slate-500 hover:text-slate-800 hover:bg-white/40'
            }`}
          >
            <ListTodo className="w-3.5 h-3.5" />
            <span>WordPress Admin</span>
          </motion.button>

          <motion.button
            whileTap={{ scale: 0.94 }}
            onClick={() => {
              setActivePane('code');
              showToast('Opened WordPress Plugin Code Exporter', 'info');
            }}
            className={`flex items-center space-x-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold transition duration-150 cursor-pointer ${
              activePane === 'code' 
                ? 'bg-white text-indigo-600 shadow-sm border border-slate-200/40 font-bold' 
                : 'text-slate-500 hover:text-slate-800 hover:bg-white/40'
            }`}
          >
            <FileCode className="w-3.5 h-3.5" />
            <span>Plugin Exporter</span>
          </motion.button>
        </div>

        {/* Direct Download Action Button */}
        <a
          href="/yasmine-artistry-booking.zip"
          download="yasmine-artistry-booking-v1.2.zip"
          onClick={() => showToast('Downloading yasmine-artistry-booking-v1.2.zip...', 'success')}
          className="flex items-center space-x-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-2xl shadow-sm hover:shadow transition duration-150 cursor-pointer flex-shrink-0"
        >
          <Download className="w-4 h-4" />
          <span>Download Plugin .ZIP</span>
        </a>
      </header>

      {/* Main Dual-Column Content */}
      <main id="app-viewport-pane" className="flex-1 grid grid-cols-1 lg:grid-cols-12 gap-6 overflow-hidden">
        {/* Left Side: Interactive Playground Viewports */}
        <div className="lg:col-span-8 flex flex-col h-full space-y-6">
          {activePane === 'simulator' && (
            <div className="flex-1 min-h-[500px]">
              <BookingFormSimulator
                services={services}
                bookings={bookings}
                businessSettings={businessSettings}
                integrations={integrations}
                onSubmitBooking={handleCreateBookingSubmit}
                onShowToast={showToast}
              />
            </div>
          )}

          {activePane === 'admin' && (
            <div className="flex-1 min-h-[500px]">
              <WpAdminSimulator
                services={services}
                bookings={bookings}
                businessSettings={businessSettings}
                integrations={integrations}
                emailTemplates={emailTemplates}
                logs={logs}
                onUpdateServices={setServices}
                onUpdateBusiness={setBusinessSettings}
                onUpdateIntegrations={setIntegrations}
                onUpdateEmailTemplates={setEmailTemplates}
                onConfirmBankTransfer={handleConfirmBankTransfer}
                onCompleteBooking={handleCompleteBooking}
                onCancelBooking={handleCancelBooking}
                onRescheduleBooking={handleRescheduleBooking}
                onTriggerCron={handleTriggerCron}
                onShowToast={showToast}
              />
            </div>
          )}

          {activePane === 'code' && (
            <div className="flex-1 min-h-[500px]">
              <CodeExporter />
            </div>
          )}
        </div>

        {/* Right Side: Constant Dispatch Logs Monitoring */}
        <div className="lg:col-span-4 flex flex-col h-full">
          <LogsPanel logs={logs} onClearLogs={handleClearLogs} />
        </div>
      </main>

      {/* Aesthetic Footer - Bento Card styled */}
      <footer id="app-footer" className="bg-white border border-slate-200/60 shadow-xs px-6 py-3.5 rounded-3xl text-center flex flex-col sm:flex-row sm:items-center sm:justify-between text-xs text-slate-500">
        <div className="flex items-center space-x-2 justify-center">
          <Activity className="w-3.5 h-3.5 text-indigo-500 animate-pulse" />
          <span>Simulation Active: Real-time Row Locking &amp; Multi-step shortcodes synced</span>
        </div>
        <div className="flex items-center space-x-1 justify-center mt-1 sm:mt-0 text-[11px]">
          <span>Crafted for WordPress modular integrations architecture</span>
          <Heart className="w-3 h-3 text-rose-500 fill-rose-500 ml-1" />
        </div>
      </footer>

      {/* Floating Animated Toast Banner */}
      <AnimatePresence>
        {toast && (
          <motion.div
            initial={{ opacity: 0, y: 50, scale: 0.9 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, y: 20, scale: 0.9 }}
            transition={{ type: "spring", stiffness: 450, damping: 25 }}
            className="fixed bottom-6 right-6 z-50 flex items-center space-x-3 bg-slate-900 text-white px-5 py-3.5 rounded-2xl shadow-2xl border border-slate-700/80 max-w-md"
          >
            <div className={`p-1.5 rounded-xl flex items-center justify-center ${
              toast.type === 'success' ? 'bg-emerald-500/20 text-emerald-400' : 
              toast.type === 'warning' ? 'bg-amber-500/20 text-amber-400' : 
              'bg-indigo-500/20 text-indigo-400'
            }`}>
              {toast.type === 'success' && <CheckCircle className="w-4 h-4" />}
              {toast.type === 'warning' && <Activity className="w-4 h-4" />}
              {toast.type === 'info' && <Sparkles className="w-4 h-4" />}
            </div>
            <div className="flex-1 text-xs font-bold leading-tight">
              {toast.message}
            </div>
            <button
              onClick={() => setToast(null)}
              className="text-slate-400 hover:text-white p-1 text-xs rounded-lg transition cursor-pointer"
            >
              ✕
            </button>
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  );
}
