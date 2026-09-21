import React from 'react';
import { motion } from 'motion/react';
import { 
  LayoutDashboard, Calendar, Wrench, Settings, Plus, Edit2, Trash2, 
  Check, X, RefreshCw, Landmark, CreditCard, Mail, Phone, MessageSquare, 
  CalendarCheck2, ChevronRight, Save, UserCheck, Eye, Sparkles, AlertCircle
} from 'lucide-react';
import { Service, Booking, BusinessSettings, IntegrationsState, EmailTemplates, NotificationLog } from '../types';

const getCurrencySymbol = (currency: string) => {
  switch (currency) {
    case 'USD': return '$';
    case 'GBP': return '£';
    case 'EUR': return '€';
    case 'NGN': return '₦';
    case 'GHS': return 'GH₵';
    case 'ZAR': return 'R';
    case 'KES': return 'KSh';
    default: return '$';
  }
};

interface WpAdminSimulatorProps {
  services: Service[];
  bookings: Booking[];
  businessSettings: BusinessSettings;
  integrations: IntegrationsState;
  emailTemplates: EmailTemplates;
  logs: NotificationLog[];
  onShowToast?: (message: string, type?: 'success' | 'info' | 'warning') => void;
  onUpdateServices: (updated: Service[]) => void;
  onUpdateBusiness: (updated: BusinessSettings) => void;
  onUpdateIntegrations: (updated: IntegrationsState) => void;
  onUpdateEmailTemplates: (updated: EmailTemplates) => void;
  onConfirmBankTransfer: (bookingId: string) => void;
  onCompleteBooking: (bookingId: string) => void;
  onCancelBooking: (bookingId: string) => void;
  onRescheduleBooking: (bookingId: string, newDateTime: string) => void;
  onTriggerCron: (cronType: 'reminders' | 'cleanup') => void;
}

export default function WpAdminSimulator({
  services,
  bookings,
  businessSettings,
  integrations,
  emailTemplates,
  logs,
  onShowToast,
  onUpdateServices,
  onUpdateBusiness,
  onUpdateIntegrations,
  onUpdateEmailTemplates,
  onConfirmBankTransfer,
  onCompleteBooking,
  onCancelBooking,
  onRescheduleBooking,
  onTriggerCron,
}: WpAdminSimulatorProps) {
  const [activeTab, setActiveTab] = React.useState<'dashboard' | 'bookings' | 'services' | 'settings'>('dashboard');
  const [settingsSubTab, setSettingsSubTab] = React.useState<'business' | 'payments' | 'google' | 'whatsapp' | 'sms' | 'emails'>('business');
  
  const symbol = getCurrencySymbol(businessSettings.currency);
  
  // Reschedule modal states
  const [rescheduleBookingId, setRescheduleBookingId] = React.useState<string | null>(null);
  const [rescheduleDate, setRescheduleDate] = React.useState<string>('');
  const [rescheduleTime, setRescheduleTime] = React.useState<string>('');

  // Service editing states
  const [isAddingService, setIsAddingService] = React.useState<boolean>(false);
  const [editingServiceId, setEditingServiceId] = React.useState<string | null>(null);
  const [serviceForm, setServiceForm] = React.useState<Omit<Service, 'id'>>({
    name: '',
    duration: 60,
    price: 100,
    depositType: 'percentage',
    depositValue: 50,
    description: '',
  });

  // Verification button states (Mock API calls)
  const [verifyingPaystack, setVerifyingPaystack] = React.useState<boolean>(false);
  const [verifyingGoogle, setVerifyingGoogle] = React.useState<boolean>(false);
  const [verifyingWhatsApp, setVerifyingWhatsApp] = React.useState<boolean>(false);
  const [verifyingTwilio, setVerifyingTwilio] = React.useState<boolean>(false);

  // Email template focus state
  const [selectedTemplateKey, setSelectedTemplateKey] = React.useState<keyof EmailTemplates>('booking_created_customer');
  const [emailTemplateForm, setEmailTemplateForm] = React.useState({
    subject: '',
    body: '',
  });

  // Sync state with parent template when selection changes
  React.useEffect(() => {
    if (emailTemplates[selectedTemplateKey]) {
      setEmailTemplateForm({
        subject: emailTemplates[selectedTemplateKey].subject,
        body: emailTemplates[selectedTemplateKey].body,
      });
    }
  }, [selectedTemplateKey, emailTemplates]);

  // Business settings state
  const [businessForm, setBusinessForm] = React.useState<BusinessSettings>(businessSettings);
  React.useEffect(() => {
    setBusinessForm(businessSettings);
  }, [businessSettings]);

  // Keys form states (uncommitted inputs)
  const [paystackInput, setPaystackInput] = React.useState({
    publicKey: integrations.paystack.publicKey,
    secretKey: integrations.paystack.secretKey,
    testMode: integrations.paystack.testMode,
  });
  const [whatsappInput, setWhatsappInput] = React.useState({
    accessToken: integrations.whatsapp.accessToken,
    phoneId: integrations.whatsapp.phoneId,
    templateName: integrations.whatsapp.templateName,
  });
  const [twilioInput, setTwilioInput] = React.useState({
    accountSid: integrations.twilio.accountSid,
    authToken: integrations.twilio.authToken,
    senderPhone: integrations.twilio.senderPhone,
  });

  // Calculate high-level stats
  const totalIncome = bookings
    .filter(b => b.status === 'confirmed' || b.status === 'completed')
    .reduce((sum, b) => sum + b.depositPaid + (b.balancePaid ? b.balanceDue : 0), 0);

  const pendingConfirmationCount = bookings.filter(
    b => b.status === 'pending_payment' && b.paymentMethod === 'bank_transfer'
  ).length;

  const totalBookingsCount = bookings.length;

  // Handles: Service offering editing
  const handleSaveService = (e: React.FormEvent) => {
    e.preventDefault();
    if (editingServiceId) {
      // Edit
      const updated = services.map(s => s.id === editingServiceId ? { ...s, ...serviceForm } : s);
      onUpdateServices(updated);
      setEditingServiceId(null);
      onShowToast?.(`Updated service: ${serviceForm.name}`, 'success');
    } else {
      // Add
      const newService: Service = {
        id: serviceForm.name.toLowerCase().replace(/\s+/g, '-'),
        ...serviceForm
      };
      onUpdateServices([...services, newService]);
      setIsAddingService(false);
      onShowToast?.(`Added new service: ${serviceForm.name}`, 'success');
    }
    // reset form
    setServiceForm({
      name: '',
      duration: 60,
      price: 100,
      depositType: 'percentage',
      depositValue: 50,
      description: '',
    });
  };

  const handleStartEditService = (s: Service) => {
    setEditingServiceId(s.id);
    setServiceForm({
      name: s.name,
      duration: s.duration,
      price: s.price,
      depositType: s.depositType,
      depositValue: s.depositValue,
      description: s.description,
    });
    setIsAddingService(true);
  };

  const handleDeleteService = (id: string) => {
    if (confirm('Are you sure you want to delete this service offering?')) {
      onUpdateServices(services.filter(s => s.id !== id));
      onShowToast?.('Service deleted successfully.', 'warning');
    }
  };

  // Handles: Business form submission
  const handleSaveBusiness = (e: React.FormEvent) => {
    e.preventDefault();
    onUpdateBusiness(businessForm);
    onShowToast?.('Business settings and bank details updated successfully!', 'success');
  };

  // Handles: Payment Key validation
  const handleVerifyPaystack = async () => {
    if (!paystackInput.publicKey || !paystackInput.secretKey) {
      alert('Please fill in both Paystack Public and Secret keys first.');
      return;
    }
    setVerifyingPaystack(true);
    // Simulate API connection verification
    await new Promise(resolve => setTimeout(resolve, 1200));
    setVerifyingPaystack(false);

    // Commit keys and enable validated status
    onUpdateIntegrations({
      ...integrations,
      paystack: {
        ...integrations.paystack,
        publicKey: paystackInput.publicKey,
        secretKey: paystackInput.secretKey,
        testMode: paystackInput.testMode,
        validated: true,
        enabled: true, // Auto enable upon verification for smooth UX!
      }
    });
    onShowToast?.('Paystack API keys verified and activated!', 'success');
  };

  // Handles: Google OAuth Connect flow
  const handleConnectGoogle = async () => {
    setVerifyingGoogle(true);
    // Simulate OAuth consent popup
    await new Promise(resolve => setTimeout(resolve, 1500));
    setVerifyingGoogle(false);

    onUpdateIntegrations({
      ...integrations,
      googleCalendar: {
        ...integrations.googleCalendar,
        connected: true,
        email: 'godsfavourinnocent@gmail.com',
        validated: true,
        enabled: true,
      }
    });
    onShowToast?.('Google Calendar OAuth connected!', 'success');
  };

  // Handles: WhatsApp API Key Validation
  const handleVerifyWhatsApp = async () => {
    if (!whatsappInput.accessToken || !whatsappInput.phoneId || !whatsappInput.templateName) {
      alert('Please fill in Meta Access Token, Phone Number ID, and Template Name.');
      return;
    }
    setVerifyingWhatsApp(true);
    await new Promise(resolve => setTimeout(resolve, 1200));
    setVerifyingWhatsApp(false);

    onUpdateIntegrations({
      ...integrations,
      whatsapp: {
        ...integrations.whatsapp,
        accessToken: whatsappInput.accessToken,
        phoneId: whatsappInput.phoneId,
        templateName: whatsappInput.templateName,
        validated: true,
        enabled: true,
      }
    });
    onShowToast?.('WhatsApp Cloud API connected and verified!', 'success');
  };

  // Handles: Twilio SMS validation
  const handleVerifyTwilio = async () => {
    if (!twilioInput.accountSid || !twilioInput.authToken || !twilioInput.senderPhone) {
      alert('Please fill in Twilio SID, Auth Token, and Sender Number.');
      return;
    }
    setVerifyingTwilio(true);
    await new Promise(resolve => setTimeout(resolve, 1200));
    setVerifyingTwilio(false);

    onUpdateIntegrations({
      ...integrations,
      twilio: {
        ...integrations.twilio,
        accountSid: twilioInput.accountSid,
        authToken: twilioInput.authToken,
        senderPhone: twilioInput.senderPhone,
        validated: true,
        enabled: true,
      }
    });
    onShowToast?.('Twilio SMS gateway connected and verified!', 'success');
  };

  // Handles: Toggle activation controls
  const handleToggleModule = (moduleKey: keyof IntegrationsState) => {
    if (!integrations[moduleKey].validated) {
      alert('You must provide valid credentials and test the connection before enabling this integration.');
      return;
    }
    const nextState = !integrations[moduleKey].enabled;
    onUpdateIntegrations({
      ...integrations,
      [moduleKey]: {
        ...integrations[moduleKey],
        enabled: nextState
      }
    });
    onShowToast?.(`${moduleKey.toUpperCase()} integration ${nextState ? 'enabled' : 'disabled'}.`, 'info');
  };

  // Handles: Email template saving
  const handleSaveEmailTemplate = (e: React.FormEvent) => {
    e.preventDefault();
    onUpdateEmailTemplates({
      ...emailTemplates,
      [selectedTemplateKey]: {
        ...emailTemplates[selectedTemplateKey],
        subject: emailTemplateForm.subject,
        body: emailTemplateForm.body,
      }
    });
    onShowToast?.('Email notification template saved!', 'success');
  };

  // Handles: Rescheduling submit
  const handleRescheduleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (rescheduleBookingId && rescheduleDate && rescheduleTime) {
      onRescheduleBooking(rescheduleBookingId, `${rescheduleDate}T${rescheduleTime}:00`);
      setRescheduleBookingId(null);
      setRescheduleDate('');
      setRescheduleTime('');
      onShowToast?.('Booking rescheduled successfully!', 'success');
    }
  };

  return (
    <div id="wp-admin-main-grid" className="flex flex-col md:flex-row h-full min-h-[500px] border border-slate-200/60 rounded-3xl overflow-hidden bg-slate-50/50 text-slate-800 font-sans shadow-xs">
      {/* WordPress Sidebar */}
      <div id="wp-admin-sidebar" className="w-full md:w-56 bg-slate-950 text-slate-300 flex-shrink-0 flex flex-col">
        <div className="px-5 py-5 bg-slate-900 border-b border-slate-800 flex items-center space-x-3">
          <div className="w-7 h-7 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-extrabold text-sm shadow-sm">
            W
          </div>
          <div>
            <span className="font-extrabold text-xs tracking-wider uppercase text-slate-100 block leading-none">WordPress Panel</span>
            <span className="text-[9px] text-slate-500 font-mono mt-1.5 block">v6.4.2 stable</span>
          </div>
        </div>

        <nav className="flex-1 py-5 space-y-1 px-3">
          <motion.button
            whileTap={{ scale: 0.96 }}
            onClick={() => setActiveTab('dashboard')}
            className={`w-full flex items-center space-x-2.5 px-3.5 py-2.5 rounded-xl text-xs font-bold transition duration-150 cursor-pointer ${
              activeTab === 'dashboard' ? 'bg-indigo-600/15 text-indigo-400 font-bold border border-indigo-500/20' : 'text-slate-400 hover:bg-slate-900 hover:text-slate-100'
            }`}
          >
            <LayoutDashboard className="w-4 h-4" />
            <span>Plugin Dashboard</span>
          </motion.button>

          <motion.button
            whileTap={{ scale: 0.96 }}
            onClick={() => setActiveTab('bookings')}
            className={`w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-bold transition duration-150 cursor-pointer ${
              activeTab === 'bookings' ? 'bg-indigo-600/15 text-indigo-400 font-bold border border-indigo-500/20' : 'text-slate-400 hover:bg-slate-900 hover:text-slate-100'
            }`}
          >
            <div className="flex items-center space-x-2.5">
              <Calendar className="w-4 h-4" />
              <span>Bookings list</span>
            </div>
            {pendingConfirmationCount > 0 && (
              <span className="bg-amber-600 text-white font-bold text-[9px] px-1.5 py-0.5 rounded-full animate-pulse">
                {pendingConfirmationCount}
              </span>
            )}
          </motion.button>

          <motion.button
            whileTap={{ scale: 0.96 }}
            onClick={() => setActiveTab('services')}
            className={`w-full flex items-center space-x-2.5 px-3.5 py-2.5 rounded-xl text-xs font-bold transition duration-150 cursor-pointer ${
              activeTab === 'services' ? 'bg-indigo-600/15 text-indigo-400 font-bold border border-indigo-500/20' : 'text-slate-400 hover:bg-slate-900 hover:text-slate-100'
            }`}
          >
            <Wrench className="w-4 h-4" />
            <span>Service Offerings</span>
          </motion.button>

          <motion.button
            whileTap={{ scale: 0.96 }}
            onClick={() => setActiveTab('settings')}
            className={`w-full flex items-center space-x-2.5 px-3.5 py-2.5 rounded-xl text-xs font-bold transition duration-150 cursor-pointer ${
              activeTab === 'settings' ? 'bg-indigo-600/15 text-indigo-400 font-bold border border-indigo-500/20' : 'text-slate-400 hover:bg-slate-900 hover:text-slate-100'
            }`}
          >
            <Settings className="w-4 h-4" />
            <span>Integration Settings</span>
          </motion.button>
        </nav>

        {/* Quick Cron Triggers Box */}
        <div className="p-4 bg-slate-900 border-t border-slate-800 space-y-2.5">
          <span className="text-[9px] font-extrabold text-slate-500 tracking-wider uppercase">WP-Cron Schedulers</span>
          <div className="space-y-2">
            <motion.button
              whileTap={{ scale: 0.94 }}
              onClick={() => {
                onTriggerCron('reminders');
                onShowToast?.('⚡ WP-Cron: Scheduled reminders dispatched via WhatsApp / Email!', 'success');
              }}
              className="w-full flex items-center justify-between px-3 py-1.5 bg-slate-950 hover:bg-slate-850 border border-slate-800 rounded-lg text-[10px] text-slate-300 font-semibold transition cursor-pointer"
            >
              <span>Trigger Reminders (T-24h)</span>
              <RefreshCw className="w-3 h-3 text-indigo-400" />
            </motion.button>
            <motion.button
              whileTap={{ scale: 0.94 }}
              onClick={() => {
                onTriggerCron('cleanup');
                onShowToast?.('🧹 WP-Cron: Unpaid session cleanup completed!', 'info');
              }}
              className="w-full flex items-center justify-between px-3 py-1.5 bg-slate-950 hover:bg-slate-850 border border-slate-800 rounded-lg text-[10px] text-slate-300 font-semibold transition cursor-pointer"
            >
              <span>Run Unpaid Session Cleanup</span>
              <X className="w-3 h-3 text-rose-500" />
            </motion.button>
          </div>
        </div>
      </div>

      {/* Main Content Area */}
      <div id="wp-admin-main-viewport" className="flex-1 flex flex-col min-w-0 bg-slate-50/40 overflow-y-auto">
        {/* Header Ribbon */}
        <div className="px-6 py-5 bg-white border-b border-slate-150 flex items-center justify-between flex-shrink-0">
          <div>
            <span className="text-slate-400 text-[9px] font-extrabold uppercase tracking-widest">wp-admin / plugins</span>
            <h1 className="text-base font-extrabold text-slate-800 mt-0.5">
              {activeTab === 'dashboard' && 'Dashboard Overview'}
              {activeTab === 'bookings' && 'Bookings Management'}
              {activeTab === 'services' && 'Services Configuration'}
              {activeTab === 'settings' && 'Plugin Configurations'}
            </h1>
          </div>
          <div className="flex items-center space-x-3 text-xs">
            <div className="text-right">
              <span className="block text-slate-400 text-[9px] font-bold uppercase tracking-wider">Business Name</span>
              <span className="font-extrabold text-slate-800">{businessSettings.name}</span>
            </div>
            <div 
              className="w-5 h-5 rounded-full border border-slate-200/60 shadow-xs"
              style={{ backgroundColor: businessSettings.brandColor }}
              title="Branding Accent Color"
            />
          </div>
        </div>

        {/* Tab Content rendering */}
        <div className="p-6 flex-1">
          {/* 1. DASHBOARD OVERVIEW */}
          {activeTab === 'dashboard' && (
            <div id="dashboard-tab-view" className="space-y-6">
              {/* Analytics Cards Row */}
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div className="bg-white border border-slate-200/60 rounded-3xl p-5 shadow-xs">
                  <span className="text-slate-400 text-[10px] font-extrabold uppercase tracking-wider">Total Sales (Confirmed)</span>
                  <div className="flex items-baseline space-x-1.5 mt-2">
                    <span className="text-2xl font-black text-slate-800">{symbol}{totalIncome.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                  </div>
                  <p className="text-[10px] text-indigo-600 mt-2 font-bold">✨ Real-time transaction tracking</p>
                </div>

                <div className="bg-white border border-slate-200/60 rounded-3xl p-5 shadow-xs">
                  <span className="text-slate-400 text-[10px] font-extrabold uppercase tracking-wider">Scheduled Sessions</span>
                  <div className="flex items-baseline space-x-1.5 mt-2">
                    <span className="text-2xl font-black text-slate-800">{totalBookingsCount}</span>
                  </div>
                  <p className="text-[10px] text-slate-400 mt-2 font-medium">Includes pending, paid &amp; cancelled</p>
                </div>

                <div className="bg-white border border-slate-200/60 rounded-3xl p-5 shadow-xs relative overflow-hidden">
                  <span className="text-slate-400 text-[10px] font-extrabold uppercase tracking-wider">Active Modules Status</span>
                  <div className="flex flex-wrap gap-1.5 mt-3">
                    {Object.entries(integrations).map(([key, config]) => (
                      <span 
                        key={key} 
                        className={`inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase border ${
                          config.enabled 
                            ? 'bg-indigo-50 text-indigo-700 border-indigo-150' 
                            : 'bg-slate-100 text-slate-400 border-slate-200/60'
                        }`}
                      >
                        <span className={`w-1.5 h-1.5 rounded-full ${config.enabled ? 'bg-indigo-600' : 'bg-slate-300'}`} />
                        <span>{key === 'googleCalendar' ? 'GCal' : key}</span>
                      </span>
                    ))}
                  </div>
                  <p className="text-[10px] text-slate-400 mt-2 font-medium">Automatic system sync enabled</p>
                </div>
              </div>

              {/* Integrations Grid & Quick Tips */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {/* Integration Modules Status */}
                <div className="bg-white border border-slate-200/60 rounded-3xl p-6 shadow-xs space-y-4">
                  <h3 className="text-sm font-extrabold text-slate-800">Toggleable Integration Modules</h3>
                  <p className="text-xs text-slate-500 leading-relaxed">
                    Under Yasmine Booking's isolated architecture, integrations stay strictly locks-disabled until credentials have been supplied and validated. 
                  </p>
                  
                  <div className="space-y-3 pt-2">
                    {/* Paystack Card */}
                    <div className="flex items-center justify-between p-3.5 border border-slate-200/60 rounded-2xl bg-slate-50/20">
                      <div className="flex items-center space-x-3">
                        <CreditCard className="w-5 h-5 text-indigo-600" />
                        <div>
                          <h4 className="text-xs font-bold text-slate-800">Paystack Automated Checkout</h4>
                          <span className="text-[10px] text-slate-400 block mt-0.5">
                            {integrations.paystack.validated ? '✅ Keys Validated' : '❌ Keys Missing'}
                          </span>
                        </div>
                      </div>
                      <button
                        onClick={() => handleToggleModule('paystack')}
                        className={`relative inline-flex h-5.5 w-10.5 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none ${
                          integrations.paystack.enabled ? 'bg-indigo-600' : 'bg-slate-200'
                        }`}
                      >
                        <span className={`pointer-events-none inline-block h-4.5 w-4.5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out ${
                          integrations.paystack.enabled ? 'translate-x-5' : 'translate-x-0'
                        }`} />
                      </button>
                    </div>

                    {/* Google Calendar Card */}
                    <div className="flex items-center justify-between p-3.5 border border-slate-200/60 rounded-2xl bg-slate-50/20">
                      <div className="flex items-center space-x-3">
                        <Calendar className="w-5 h-5 text-rose-500" />
                        <div>
                          <h4 className="text-xs font-bold text-slate-800">Google Calendar OAuth Sync</h4>
                          <span className="text-[10px] text-slate-400 block mt-0.5">
                            {integrations.googleCalendar.connected ? `✅ Synced to ${integrations.googleCalendar.email}` : '❌ Disconnected'}
                          </span>
                        </div>
                      </div>
                      <button
                        onClick={() => handleToggleModule('googleCalendar')}
                        className={`relative inline-flex h-5.5 w-10.5 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none ${
                          integrations.googleCalendar.enabled ? 'bg-indigo-600' : 'bg-slate-200'
                        }`}
                      >
                        <span className={`pointer-events-none inline-block h-4.5 w-4.5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out ${
                          integrations.googleCalendar.enabled ? 'translate-x-5' : 'translate-x-0'
                        }`} />
                      </button>
                    </div>

                    {/* WhatsApp Business Card */}
                    <div className="flex items-center justify-between p-3.5 border border-slate-200/60 rounded-2xl bg-slate-50/20">
                      <div className="flex items-center space-x-3">
                        <MessageSquare className="w-5 h-5 text-indigo-500" />
                        <div>
                          <h4 className="text-xs font-bold text-slate-800">WhatsApp Meta Cloud Alerts</h4>
                          <span className="text-[10px] text-slate-400 block mt-0.5">
                            {integrations.whatsapp.validated ? '✅ Meta API Verified' : '❌ Credentials Missing'}
                          </span>
                        </div>
                      </div>
                      <button
                        onClick={() => handleToggleModule('whatsapp')}
                        className={`relative inline-flex h-5.5 w-10.5 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none ${
                          integrations.whatsapp.enabled ? 'bg-indigo-600' : 'bg-slate-200'
                        }`}
                      >
                        <span className={`pointer-events-none inline-block h-4.5 w-4.5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out ${
                          integrations.whatsapp.enabled ? 'translate-x-5' : 'translate-x-0'
                        }`} />
                      </button>
                    </div>

                    {/* Twilio Card */}
                    <div className="flex items-center justify-between p-3.5 border border-slate-200/60 rounded-2xl bg-slate-50/20">
                      <div className="flex items-center space-x-3">
                        <Phone className="w-5 h-5 text-sky-500" />
                        <div>
                          <h4 className="text-xs font-bold text-slate-800">Twilio Automated SMS Reminders</h4>
                          <span className="text-[10px] text-slate-400 block mt-0.5">
                            {integrations.twilio.validated ? '✅ Twilio API Active' : '❌ Credentials Missing'}
                          </span>
                        </div>
                      </div>
                      <button
                        onClick={() => handleToggleModule('twilio')}
                        className={`relative inline-flex h-5.5 w-10.5 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none ${
                          integrations.twilio.enabled ? 'bg-indigo-600' : 'bg-slate-200'
                        }`}
                      >
                        <span className={`pointer-events-none inline-block h-4.5 w-4.5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out ${
                          integrations.twilio.enabled ? 'translate-x-5' : 'translate-x-0'
                        }`} />
                      </button>
                    </div>
                  </div>
                </div>

                {/* Operations Insights / Pending Approvals */}
                <div className="bg-white border border-slate-200/60 rounded-3xl p-6 shadow-xs space-y-4">
                  <h3 className="text-sm font-extrabold text-slate-800">Operational Actions Pending</h3>
                  
                  {pendingConfirmationCount === 0 ? (
                    <div className="py-12 border border-dashed border-slate-200 rounded-2xl text-center space-y-2.5 text-slate-400">
                      <Check className="w-6 h-6 text-indigo-600 mx-auto" />
                      <p className="text-xs font-semibold">All payment reconciliations completed!</p>
                    </div>
                  ) : (
                    <div className="space-y-3.5 max-h-72 overflow-y-auto pr-1">
                      {bookings
                        .filter(b => b.status === 'pending_payment' && b.paymentMethod === 'bank_transfer')
                        .map(b => {
                          const service = services.find(s => s.id === b.serviceId);
                          return (
                            <div key={b.id} className="p-4 bg-amber-50 border border-amber-200 rounded-2xl flex items-center justify-between shadow-xs">
                              <div>
                                <span className="font-mono text-[10px] font-extrabold text-amber-800 block">ID: {b.id} ({b.referenceCode})</span>
                                <span className="font-bold text-xs text-slate-800 block mt-1">{b.customerName}</span>
                                <span className="text-[10px] text-slate-500 block mt-0.5">{service?.name} • Deposit: {symbol}{b.depositPaid}</span>
                              </div>
                              <motion.button
                                whileTap={{ scale: 0.94 }}
                                onClick={() => {
                                  onConfirmBankTransfer(b.id);
                                  onShowToast?.(`Bank transfer verified for ${b.customerName}! Appointment confirmed.`, 'success');
                                }}
                                className="flex items-center space-x-1 px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-[10px] font-bold transition cursor-pointer shadow-xs"
                              >
                                <Check className="w-3.5 h-3.5 stroke-[2.5]" />
                                <span>Confirm Wire</span>
                              </motion.button>
                            </div>
                          );
                        })}
                    </div>
                  )}

                  {/* System health quick check */}
                  <div className="border-t border-slate-100 pt-4 text-xs space-y-2 text-slate-500">
                    <span className="font-bold text-slate-600 text-[11px] block">Cron Status Monitor:</span>
                    <div className="flex items-center justify-between text-[11px]">
                      <span>yasmine_booking_cron_reminders:</span>
                      <span className="font-mono font-bold text-indigo-600">Active (Hourly)</span>
                    </div>
                    <div className="flex items-center justify-between text-[11px]">
                      <span>yasmine_booking_cron_cleanup:</span>
                      <span className="font-mono font-bold text-indigo-600">Active (Twice Daily)</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* 2. BOOKINGS LIST TABLE */}
          {activeTab === 'bookings' && (
            <div id="bookings-tab-view" className="bg-white border border-slate-200/60 rounded-3xl overflow-hidden shadow-xs">
              <div className="overflow-x-auto">
                <table className="w-full text-left border-collapse">
                  <thead>
                    <tr className="bg-slate-50/50 border-b border-slate-200/60 text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">
                      <th className="px-6 py-4">Client Info</th>
                      <th className="px-6 py-4">Service</th>
                      <th className="px-6 py-4">Schedule</th>
                      <th className="px-6 py-4">Status</th>
                      <th className="px-6 py-4">Method</th>
                      <th className="px-6 py-4">Fees (Deposit / Bal)</th>
                      <th className="px-6 py-4 text-right">Actions</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100 text-xs">
                    {bookings.length === 0 ? (
                      <tr>
                        <td colSpan={7} className="px-6 py-12 text-center text-slate-400">
                          No appointments booked yet.
                        </td>
                      </tr>
                    ) : (
                      bookings.map((b) => {
                        const service = services.find(s => s.id === b.serviceId);
                        const isExpiredPending = b.status === 'pending_payment' && 
                          (Date.now() - new Date(b.createdAt).getTime() > 1800000); // 30 mins

                        return (
                          <tr key={b.id} className="hover:bg-slate-50/40 transition">
                            <td className="px-6 py-4">
                              <span className="font-bold text-slate-800">{b.customerName}</span>
                              <span className="block font-mono text-[10px] text-slate-400 mt-1">{b.customerEmail}</span>
                              <span className="block font-mono text-[10px] text-slate-400">{b.customerPhone}</span>
                              {b.customerAddress && (
                                <span className="block text-[10px] text-slate-500 mt-0.5 font-medium">📍 {b.customerAddress}</span>
                              )}
                            </td>
                            <td className="px-6 py-4">
                              <span className="font-semibold text-slate-800">{service?.name || b.serviceId}</span>
                              <span className="block text-[10px] text-slate-400 mt-1">{service?.duration} mins</span>
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap">
                              <span className="font-semibold text-slate-800">
                                {new Date(b.dateTime).toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' })}
                              </span>
                              <span className="block font-bold text-[10px] text-indigo-600 mt-1">
                                {new Date(b.dateTime).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                              </span>
                            </td>
                            <td className="px-6 py-4">
                              <span className={`inline-flex px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase ${
                                b.status === 'confirmed' 
                                  ? 'bg-indigo-50 text-indigo-700 border border-indigo-150' 
                                  : b.status === 'pending_payment' 
                                  ? 'bg-amber-50 text-amber-700 border border-amber-150'
                                  : b.status === 'completed'
                                  ? 'bg-emerald-50 text-emerald-700 border border-emerald-150'
                                  : 'bg-rose-50 text-rose-700 border border-rose-150'
                              }`}>
                                {b.status}
                              </span>
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap font-mono text-[10px] uppercase text-slate-400">
                              {b.paymentMethod === 'paystack' ? 'Paystack' : 'Manual Bank'}
                            </td>
                            <td className="px-6 py-4">
                              <div className="text-slate-700">
                                Dep: <span className="font-bold text-slate-800">{symbol}{b.depositPaid}</span>
                              </div>
                              <div className="text-[10px] text-slate-400 mt-1">
                                Bal: <span className={`font-semibold ${b.balancePaid ? 'text-indigo-600 line-through' : 'text-slate-400'}`}>
                                  {symbol}{b.balanceDue} {b.balancePaid && '(Paid)'}
                                </span>
                              </div>
                            </td>
                            <td className="px-6 py-4 text-right space-y-1.5 whitespace-nowrap">
                              {/* 1. Confirm Wire if pending & manual wire */}
                              {b.status === 'pending_payment' && b.paymentMethod === 'bank_transfer' && (
                                <motion.button
                                  whileTap={{ scale: 0.94 }}
                                  onClick={() => {
                                    onConfirmBankTransfer(b.id);
                                    onShowToast?.(`Verified bank payment for ${b.customerName}. Booking confirmed!`, 'success');
                                  }}
                                  className="inline-flex items-center space-x-1 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-[10px] font-bold transition ml-1.5 cursor-pointer shadow-xs"
                                >
                                  <Check className="w-3 h-3 stroke-[2.5]" />
                                  <span>Confirm Wire</span>
                                </motion.button>
                              )}

                              {/* 2. Complete Booking */}
                              {b.status === 'confirmed' && (
                                <motion.button
                                  whileTap={{ scale: 0.94 }}
                                  onClick={() => {
                                    onCompleteBooking(b.id);
                                    onShowToast?.(`Appointment for ${b.customerName} marked as completed!`, 'success');
                                  }}
                                  className="inline-flex items-center space-x-1 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-[10px] font-bold transition ml-1.5 cursor-pointer shadow-xs"
                                  title="Mark completed and trigger balance due invoice"
                                >
                                  <UserCheck className="w-3 h-3 stroke-[2.5]" />
                                  <span>Mark Completed</span>
                                </motion.button>
                              )}

                              {/* 3. Action Menu */}
                              {b.status !== 'cancelled' && b.status !== 'completed' && (
                                <div className="inline-flex items-center space-x-1.5 ml-1.5">
                                  <motion.button
                                    whileTap={{ scale: 0.94 }}
                                    onClick={() => {
                                      setRescheduleBookingId(b.id);
                                      setRescheduleDate(b.dateTime.split('T')[0]);
                                      setRescheduleTime(b.dateTime.split('T')[1]?.substring(0, 5) || '09:00');
                                    }}
                                    className="px-2.5 py-1.5 border border-slate-200/60 hover:border-slate-300 hover:bg-slate-50 rounded-xl text-[10px] font-bold text-slate-600 transition cursor-pointer"
                                  >
                                    Reschedule
                                  </motion.button>
                                  <motion.button
                                    whileTap={{ scale: 0.94 }}
                                    onClick={() => {
                                      if (confirm('Cancel this scheduled appointment? This releases the slot.')) {
                                        onCancelBooking(b.id);
                                        onShowToast?.(`Appointment for ${b.customerName} has been cancelled.`, 'warning');
                                      }
                                    }}
                                    className="px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-150 rounded-xl text-[10px] font-bold transition cursor-pointer"
                                  >
                                    Cancel
                                  </motion.button>
                                </div>
                              )}
                            </td>
                          </tr>
                        );
                      })
                    )}
                  </tbody>
                </table>
              </div>

              {/* Reschedule Dialog overlay */}
              {rescheduleBookingId && (
                <div className="fixed inset-0 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 z-50">
                  <div className="bg-white rounded-3xl border border-slate-200/60 shadow-xl p-6 max-w-sm w-full space-y-4">
                    <div className="flex items-center justify-between border-b border-slate-100 pb-3">
                      <h3 className="font-extrabold text-sm text-slate-800">Reschedule Appointment</h3>
                      <button 
                        onClick={() => setRescheduleBookingId(null)}
                        className="p-1.5 rounded-full hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition"
                      >
                        <X className="w-4 h-4" />
                      </button>
                    </div>

                    <form onSubmit={handleRescheduleSubmit} className="space-y-4 text-xs text-left">
                      <div>
                        <label className="block font-bold text-slate-500 mb-1.5">New Appointment Date</label>
                        <input
                          type="date"
                          required
                          value={rescheduleDate}
                          onChange={(e) => setRescheduleDate(e.target.value)}
                          min={new Date().toISOString().split('T')[0]}
                          className="w-full border border-slate-200 rounded-xl p-2.5 bg-slate-50/50 focus:outline-none focus:border-indigo-500 font-semibold"
                        />
                      </div>

                      <div>
                        <label className="block font-bold text-slate-500 mb-1.5">New Appointment Time</label>
                        <input
                          type="time"
                          required
                          value={rescheduleTime}
                          onChange={(e) => setRescheduleTime(e.target.value)}
                          className="w-full border border-slate-200 rounded-xl p-2.5 bg-slate-50/50 focus:outline-none focus:border-indigo-500 font-semibold"
                        />
                      </div>

                      <div className="flex justify-end space-x-2 pt-2">
                        <button
                          type="button"
                          onClick={() => setRescheduleBookingId(null)}
                          className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition"
                        >
                          Abort
                        </button>
                        <button
                          type="submit"
                          className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-xs transition"
                        >
                          Commit Reschedule
                        </button>
                      </div>
                    </form>
                  </div>
                </div>
              )}
            </div>
          )}

          {/* 3. SERVICE OFFERINGS CONFIG */}
          {activeTab === 'services' && (
            <div id="services-tab-view" className="space-y-6">
              {/* Add / Edit Form */}
              {isAddingService ? (
                <form onSubmit={handleSaveService} className="bg-white border border-slate-200/60 rounded-3xl p-6 shadow-xs space-y-4 max-w-xl text-xs text-left">
                  <div className="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 className="font-extrabold text-sm text-slate-800">
                      {editingServiceId ? 'Edit Service Offering' : 'Add New Service Offering'}
                    </h3>
                    <button 
                      type="button"
                      onClick={() => {
                        setIsAddingService(false);
                        setEditingServiceId(null);
                      }}
                      className="p-1.5 rounded-full hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition"
                    >
                      <X className="w-4 h-4" />
                    </button>
                  </div>

                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                      <label className="block font-bold text-slate-500 mb-1.5">Service Name *</label>
                      <input
                        type="text"
                        required
                        placeholder="E.g. Bridal Make-up Session"
                        value={serviceForm.name}
                        onChange={(e) => setServiceForm({ ...serviceForm, name: e.target.value })}
                        className="w-full border border-slate-200 rounded-xl p-2.5 bg-slate-50/50 focus:outline-none focus:border-indigo-500 font-semibold"
                      />
                    </div>

                    <div>
                      <label className="block font-bold text-slate-500 mb-1.5">Duration (Minutes) *</label>
                      <input
                        type="number"
                        required
                        min={10}
                        max={480}
                        value={serviceForm.duration}
                        onChange={(e) => setServiceForm({ ...serviceForm, duration: parseInt(e.target.value) || 60 })}
                        className="w-full border border-slate-200 rounded-xl p-2.5 bg-slate-50/50 focus:outline-none focus:border-indigo-500 font-semibold"
                      />
                    </div>

                    <div>
                      <label className="block font-bold text-slate-500 mb-1.5">Full Pricing ($ USD) *</label>
                      <input
                        type="number"
                        required
                        min={0}
                        value={serviceForm.price}
                        onChange={(e) => setServiceForm({ ...serviceForm, price: parseFloat(e.target.value) || 0 })}
                        className="w-full border border-slate-200 rounded-xl p-2.5 bg-slate-50/50 focus:outline-none focus:border-indigo-500 font-semibold"
                      />
                    </div>

                    <div>
                      <label className="block font-bold text-slate-500 mb-1.5">Deposit Payment Structure</label>
                      <div className="flex space-x-2">
                        <select
                          value={serviceForm.depositType}
                          onChange={(e: any) => setServiceForm({ ...serviceForm, depositType: e.target.value })}
                          className="border border-slate-200 rounded-xl p-2.5 bg-slate-50/50 focus:outline-none focus:border-indigo-500 flex-1 font-semibold"
                        >
                          <option value="percentage">Percentage (%)</option>
                          <option value="fixed">Fixed Sum ($)</option>
                        </select>
                        <input
                          type="number"
                          required
                          min={0}
                          value={serviceForm.depositValue}
                          onChange={(e) => setServiceForm({ ...serviceForm, depositValue: parseFloat(e.target.value) || 0 })}
                          className="border border-slate-200 rounded-xl p-2.5 bg-slate-50/50 focus:outline-none focus:border-indigo-500 w-24 font-semibold text-center"
                        />
                      </div>
                    </div>
                  </div>

                  <div>
                    <label className="block font-bold text-slate-500 mb-1.5">Public Description *</label>
                    <textarea
                      required
                      placeholder="Describe what is included in this booking so your clients know what to expect..."
                      rows={3}
                      value={serviceForm.description}
                      onChange={(e) => setServiceForm({ ...serviceForm, description: e.target.value })}
                      className="w-full border border-slate-200 rounded-xl p-2.5 bg-slate-50/50 focus:outline-none focus:border-indigo-500 font-semibold"
                    />
                  </div>

                  <div className="flex justify-end space-x-2 pt-3.5 border-t border-slate-100">
                    <button
                      type="button"
                      onClick={() => {
                        setIsAddingService(false);
                        setEditingServiceId(null);
                      }}
                      className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition"
                    >
                      Abort
                    </button>
                    <button
                      type="submit"
                      className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-xs transition flex items-center space-x-1.5 cursor-pointer"
                    >
                      <Save className="w-3.5 h-3.5" />
                      <span>Save Service</span>
                    </button>
                  </div>
                </form>
              ) : (
                <div className="flex justify-end">
                  <button
                    onClick={() => setIsAddingService(true)}
                    className="flex items-center space-x-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition shadow-xs cursor-pointer"
                  >
                    <Plus className="w-4 h-4 stroke-[2.5]" />
                    <span>Add New Service Offer</span>
                  </button>
                </div>
              )}

              {/* Service Cards list */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {services.map((s) => (
                  <div key={s.id} className="bg-white border border-slate-200/60 rounded-3xl p-5.5 shadow-xs flex flex-col justify-between hover:border-slate-300 transition text-left">
                    <div>
                      <div className="flex items-start justify-between">
                        <h4 className="font-extrabold text-sm text-slate-800">{s.name}</h4>
                        <span className="font-mono text-xs font-extrabold text-slate-800 bg-slate-50 border border-slate-200/60 px-3 py-1 rounded-xl">
                          {symbol}{s.price.toFixed(2)}
                        </span>
                      </div>
                      <p className="text-[11px] text-indigo-600 mt-1 font-bold">{s.duration} minutes slot duration</p>
                      <p className="text-xs text-slate-500 mt-3.5 line-clamp-3 leading-relaxed font-medium">{s.description}</p>
                    </div>

                    <div className="flex items-center justify-between border-t border-slate-100 pt-4 mt-4">
                      <div className="inline-flex items-center space-x-1 bg-amber-50 text-amber-800 text-[10px] font-bold px-3 py-1 rounded-full border border-amber-150">
                        Deposit: {s.depositType === 'percentage' ? `${s.depositValue}%` : `${symbol}${s.depositValue.toFixed(2)}`}
                      </div>
                      <div className="flex space-x-1.5">
                        <button
                          onClick={() => handleStartEditService(s)}
                          className="p-2 border border-slate-200/60 hover:border-slate-300 rounded-xl hover:bg-slate-50 text-slate-600 transition"
                          title="Edit Offering"
                        >
                          <Edit2 className="w-3.5 h-3.5" />
                        </button>
                        <button
                          onClick={() => handleDeleteService(s.id)}
                          className="p-2 bg-rose-50 hover:bg-rose-100 border border-rose-150 text-rose-600 rounded-xl transition"
                          title="Delete Offering"
                        >
                          <Trash2 className="w-3.5 h-3.5" />
                        </button>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* 4. INTEGRATION SETTINGS */}
          {activeTab === 'settings' && (
            <div id="settings-tab-view" className="bg-white border border-slate-200/60 rounded-3xl overflow-hidden shadow-xs flex flex-col md:flex-row min-h-[400px]">
              {/* Settings Sub-navigation Tabs */}
              <div className="w-full md:w-52 border-r border-slate-150 bg-slate-50/30 flex-shrink-0 flex flex-row md:flex-col p-3.5 space-x-1 md:space-x-0 md:space-y-1.5 overflow-x-auto scrollbar-none">
                {[
                  { id: 'business', name: 'Business Details', icon: Landmark },
                  { id: 'payments', name: 'Payments Gate', icon: CreditCard },
                  { id: 'google', name: 'Google Calendar', icon: Calendar },
                  { id: 'whatsapp', name: 'WhatsApp Meta', icon: MessageSquare },
                  { id: 'sms', name: 'SMS (Twilio)', icon: Phone },
                  { id: 'emails', name: 'Email Templates', icon: Mail }
                ].map((tab) => {
                  const Icon = tab.icon;
                  return (
                    <button
                      key={tab.id}
                      onClick={() => setSettingsSubTab(tab.id as any)}
                      className={`flex items-center space-x-2.5 px-3.5 py-3 rounded-xl text-xs font-bold whitespace-nowrap transition w-full text-left ${
                        settingsSubTab === tab.id 
                          ? 'bg-indigo-50 text-indigo-700 shadow-xs' 
                          : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800'
                      }`}
                    >
                      <Icon className="w-4 h-4" />
                      <span>{tab.name}</span>
                    </button>
                  );
                })}
              </div>

              {/* Sub-tab viewport */}
              <div className="flex-1 p-6 text-xs text-left">
                {/* 4a. BUSINESS DETAILS */}
                {settingsSubTab === 'business' && (
                  <form onSubmit={handleSaveBusiness} className="space-y-4 max-w-md">
                    <h3 className="text-sm font-extrabold text-slate-800 border-b border-slate-150 pb-2.5">Business Operations & Branding</h3>
                    
                    <div>
                      <label className="block font-bold text-slate-500 mb-1.5">Business Brand Name</label>
                      <input
                        type="text"
                        required
                        value={businessForm.name}
                        onChange={(e) => setBusinessForm({ ...businessForm, name: e.target.value })}
                        className="w-full border border-slate-200 rounded-xl p-2.5 bg-slate-50/50 focus:outline-none focus:border-indigo-500 font-semibold"
                      />
                    </div>

                    <div>
                      <label className="block font-bold text-slate-500 mb-1.5">Company Physical Address</label>
                      <textarea
                        required
                        rows={2}
                        value={businessForm.address}
                        onChange={(e) => setBusinessForm({ ...businessForm, address: e.target.value })}
                        placeholder="E.g. Suite 4, Yasmine Plaza, Lagos, Nigeria"
                        className="w-full border border-slate-200 rounded-xl p-2.5 bg-slate-50/50 focus:outline-none focus:border-indigo-500 font-semibold"
                      />
                      <p className="text-[10px] text-slate-400 mt-1.5 font-medium">This is the physical location of your business shown on client correspondence.</p>
                    </div>

                    {/* Bank Wire Details for Manual Payments */}
                    <div className="bg-slate-50 border border-slate-200 rounded-2xl p-4.5 space-y-3.5 shadow-sm">
                      <h4 className="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                        <Landmark className="w-3.5 h-3.5 text-indigo-500" />
                        <span>Manual Wire/Transfer Details</span>
                      </h4>
                      <p className="text-[10px] text-slate-400 font-medium">These banking details are displayed directly to clients on the booking widget when selecting manual payment.</p>
                      
                      <div className="space-y-3">
                        <div>
                          <label className="block text-[10px] font-bold text-slate-500 mb-1">Company Bank Name</label>
                          <input
                            type="text"
                            required
                            value={businessForm.bankName || ''}
                            onChange={(e) => setBusinessForm({ ...businessForm, bankName: e.target.value })}
                            placeholder="E.g. Access Bank or GTBank"
                            className="w-full border border-slate-200 rounded-lg p-2 bg-white focus:outline-none focus:border-indigo-500 font-semibold text-xs"
                          />
                        </div>

                        <div className="grid grid-cols-2 gap-2">
                          <div>
                            <label className="block text-[10px] font-bold text-slate-500 mb-1">Account Number</label>
                            <input
                              type="text"
                              required
                              value={businessForm.accountNumber || ''}
                              onChange={(e) => setBusinessForm({ ...businessForm, accountNumber: e.target.value })}
                              placeholder="E.g. 1012938475"
                              className="w-full border border-slate-200 rounded-lg p-2 bg-white focus:outline-none focus:border-indigo-500 font-semibold font-mono text-xs"
                            />
                          </div>
                          <div>
                            <label className="block text-[10px] font-bold text-slate-500 mb-1">Account Name</label>
                            <input
                              type="text"
                              required
                              value={businessForm.accountName || ''}
                              onChange={(e) => setBusinessForm({ ...businessForm, accountName: e.target.value })}
                              placeholder="E.g. Yasmine Studio Ltd"
                              className="w-full border border-slate-200 rounded-lg p-2 bg-white focus:outline-none focus:border-indigo-500 font-semibold text-xs"
                            />
                          </div>
                        </div>
                      </div>
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                      <div>
                        <label className="block font-bold text-slate-500 mb-1.5">Working Hour Start</label>
                        <input
                          type="text"
                          required
                          value={businessForm.workingHoursStart}
                          onChange={(e) => setBusinessForm({ ...businessForm, workingHoursStart: e.target.value })}
                          className="w-full border border-slate-200 rounded-xl p-2.5 bg-slate-50/50 focus:outline-none focus:border-indigo-500 font-semibold text-center"
                        />
                      </div>
                      <div>
                        <label className="block font-bold text-slate-500 mb-1.5">Working Hour End</label>
                        <input
                          type="text"
                          required
                          value={businessForm.workingHoursEnd}
                          onChange={(e) => setBusinessForm({ ...businessForm, workingHoursEnd: e.target.value })}
                          className="w-full border border-slate-200 rounded-xl p-2.5 bg-slate-50/50 focus:outline-none focus:border-indigo-500 font-semibold text-center"
                        />
                      </div>
                    </div>

                    <div>
                      <label className="block font-bold text-slate-500 mb-1.5">Booking Widget Primary Color</label>
                      <div className="flex items-center space-x-3">
                        <input
                          type="color"
                          value={businessForm.brandColor}
                          onChange={(e) => setBusinessForm({ ...businessForm, brandColor: e.target.value })}
                          className="border border-slate-200 rounded-xl w-14 h-9 p-1 cursor-pointer bg-slate-50/50"
                        />
                        <span className="font-mono text-xs font-bold text-slate-700">{businessForm.brandColor}</span>
                      </div>
                      <p className="text-[10px] text-slate-400 mt-1.5 font-medium">Select your business's brand color. It dynamically styles the booking form's steps, buttons, and selection frames!</p>
                    </div>

                    <div>
                      <label className="block font-bold text-slate-500 mb-1.5">Business Currency</label>
                      <select
                        value={businessForm.currency}
                        onChange={(e) => setBusinessForm({ ...businessForm, currency: e.target.value })}
                        className="w-full border border-slate-200 rounded-xl p-2.5 bg-slate-50/50 focus:outline-none focus:border-indigo-500 font-semibold text-slate-800"
                      >
                        <option value="USD">US Dollar ($ - USD)</option>
                        <option value="NGN">Nigerian Naira (₦ - NGN)</option>
                        <option value="GBP">British Pound (£ - GBP)</option>
                        <option value="EUR">Euro (€ - EUR)</option>
                        <option value="GHS">Ghanaian Cedi (GH₵ - GHS)</option>
                        <option value="ZAR">South African Rand (R - ZAR)</option>
                        <option value="KES">Kenyan Shilling (KSh - KES)</option>
                      </select>
                      <p className="text-[10px] text-slate-400 mt-1.5 font-medium">Select popular currencies, including Naira (NGN), USD, GBP, EUR, GHS, ZAR, KES. Updates pricing gateways and simulators instantly.</p>
                    </div>

                    <div className="border-t border-slate-150 pt-4.5 space-y-4">
                      <div className="flex items-center justify-between">
                        <div>
                          <h4 className="text-xs font-bold text-slate-800">Dynamic Variable Category Pricing</h4>
                          <p className="text-[10px] text-slate-400 font-medium">Vary booking rates based on custom rules (e.g., location, experience, tier).</p>
                        </div>
                        <button
                          type="button"
                          onClick={() => setBusinessForm({
                            ...businessForm,
                            categoryConfig: {
                              ...businessForm.categoryConfig,
                              enabled: !businessForm.categoryConfig?.enabled
                            }
                          })}
                          className={`relative inline-flex h-5.5 w-10.5 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none ${
                            businessForm.categoryConfig?.enabled ? 'bg-indigo-600' : 'bg-slate-200'
                          }`}
                        >
                          <span className={`pointer-events-none inline-block h-4.5 w-4.5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out ${
                            businessForm.categoryConfig?.enabled ? 'translate-x-5' : 'translate-x-0'
                          }`} />
                        </button>
                      </div>

                      {businessForm.categoryConfig?.enabled && (
                        <div className="bg-slate-50 border border-slate-200 p-4 rounded-2xl space-y-3.5">
                          <div>
                            <label className="block font-bold text-slate-500 mb-1 font-bold">Category Parameter Label</label>
                            <input
                              type="text"
                              required
                              value={businessForm.categoryConfig.label}
                              onChange={(e) => setBusinessForm({
                                ...businessForm,
                                categoryConfig: {
                                  ...businessForm.categoryConfig,
                                  label: e.target.value
                                }
                              })}
                              placeholder="E.g. Location, Barber Tier, Stylist Level"
                              className="w-full border border-slate-200 rounded-xl p-2 bg-white focus:outline-none focus:border-indigo-500 font-semibold"
                            />
                            <p className="text-[9px] text-slate-400 mt-1 font-medium">This label replaces standard tags on the booking form (e.g. "Stay location").</p>
                          </div>

                          <div className="space-y-2 font-semibold">
                            <div className="flex justify-between items-center">
                              <span className="font-bold text-slate-600 text-[11px] uppercase">Options & Pricing Adjustments:</span>
                              <button
                                type="button"
                                onClick={() => {
                                  const newId = 'opt-' + Date.now();
                                  const updatedOptions = [
                                    ...(businessForm.categoryConfig.options || []),
                                    { id: newId, name: 'New Option', priceValue: 0 }
                                  ];
                                  setBusinessForm({
                                    ...businessForm,
                                    categoryConfig: {
                                      ...businessForm.categoryConfig,
                                      options: updatedOptions
                                    }
                                  });
                                }}
                                className="text-[10px] font-bold text-indigo-600 hover:text-indigo-800 flex items-center space-x-1 cursor-pointer"
                              >
                                <Plus className="w-3 h-3" />
                                <span>Add Option</span>
                              </button>
                            </div>

                            <div className="space-y-2 max-h-48 overflow-y-auto pr-1">
                              {(businessForm.categoryConfig.options || []).map((opt, idx) => (
                                <div key={opt.id} className="flex items-center space-x-2 bg-white p-2 border border-slate-200 rounded-xl shadow-xs">
                                  <input
                                    type="text"
                                    required
                                    value={opt.name}
                                    onChange={(e) => {
                                      const updated = [...businessForm.categoryConfig.options];
                                      updated[idx] = { ...updated[idx], name: e.target.value };
                                      setBusinessForm({
                                        ...businessForm,
                                        categoryConfig: {
                                          ...businessForm.categoryConfig,
                                          options: updated
                                        }
                                      });
                                    }}
                                    placeholder="Name (e.g. Lekki)"
                                    className="flex-1 min-w-0 border border-slate-100 rounded-lg p-1.5 bg-slate-50/50 font-bold focus:outline-none"
                                  />
                                  <div className="flex items-center space-x-1 w-24 flex-shrink-0">
                                    <span className="text-slate-400 font-bold text-[10px]">{businessForm.currency}</span>
                                    <input
                                      type="number"
                                      required
                                      value={opt.priceValue}
                                      onChange={(e) => {
                                        const updated = [...businessForm.categoryConfig.options];
                                        updated[idx] = { ...updated[idx], priceValue: parseFloat(e.target.value) || 0 };
                                        setBusinessForm({
                                          ...businessForm,
                                          categoryConfig: {
                                            ...businessForm.categoryConfig,
                                            options: updated
                                          }
                                        });
                                      }}
                                      placeholder="Price"
                                      className="w-full border border-slate-100 rounded-lg p-1.5 bg-slate-50/50 font-mono font-bold text-center focus:outline-none"
                                    />
                                  </div>
                                  <button
                                    type="button"
                                    onClick={() => {
                                      const updated = businessForm.categoryConfig.options.filter(o => o.id !== opt.id);
                                      setBusinessForm({
                                        ...businessForm,
                                        categoryConfig: {
                                          ...businessForm.categoryConfig,
                                          options: updated
                                        }
                                      });
                                    }}
                                    className="p-1.5 bg-rose-50 text-rose-600 rounded-lg border border-rose-100 hover:bg-rose-100 cursor-pointer"
                                  >
                                    <Trash2 className="w-3.5 h-3.5" />
                                  </button>
                                </div>
                              ))}
                            </div>
                          </div>
                        </div>
                      )}
                    </div>

                    <div className="pt-2 border-t border-slate-100">
                      <button
                        type="submit"
                        className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-xs transition flex items-center space-x-1.5 cursor-pointer"
                      >
                        <Save className="w-3.5 h-3.5" />
                        <span>Update Brand Details</span>
                      </button>
                    </div>
                  </form>
                )}

                {/* 4b. PAYMENTS GATEWAY */}
                {settingsSubTab === 'payments' && (
                  <div className="space-y-5 max-w-md">
                    <div>
                      <h3 className="text-sm font-extrabold text-slate-800">Payment Processor Settings</h3>
                      <p className="text-[11px] text-slate-500 mt-1 leading-relaxed">
                        Yasmine Booking includes standard Manual Bank transfer wiring by default. Online deposits are processed via **Paystack**.
                      </p>
                    </div>

                    <div className="bg-indigo-50/40 border border-indigo-150 p-4 rounded-2xl leading-relaxed text-indigo-900 text-[11px] space-y-1">
                      <span className="font-bold block mb-0.5">ℹ️ Safe Keys Validation Hook:</span>
                      Under strict scoping rules, the Paystack toggle is locked and grayed out until API Keys are entered and the "Verify & Authorize" request succeeds.
                    </div>

                    <div className="space-y-3.5">
                      <div>
                        <label className="block font-bold text-slate-500 mb-1.5">Paystack Public Key</label>
                        <input
                          type="text"
                          placeholder="pk_test_..."
                          value={paystackInput.publicKey}
                          onChange={(e) => setPaystackInput({ ...paystackInput, publicKey: e.target.value })}
                          className="w-full border border-slate-200 rounded-xl p-2.5 font-mono text-[11px] bg-slate-50/50 focus:outline-none focus:border-indigo-500"
                        />
                      </div>

                      <div>
                        <label className="block font-bold text-slate-500 mb-1.5">Paystack Secret Key</label>
                        <input
                          type="password"
                          placeholder="sk_test_..."
                          value={paystackInput.secretKey}
                          onChange={(e) => setPaystackInput({ ...paystackInput, secretKey: e.target.value })}
                          className="w-full border border-slate-200 rounded-xl p-2.5 font-mono text-[11px] bg-slate-50/50 focus:outline-none focus:border-indigo-500"
                        />
                      </div>

                      <div className="flex items-center justify-between p-2 bg-slate-50/50 rounded-xl border border-slate-150">
                        <span className="text-slate-500 pl-1.5 font-semibold text-[11px]">Sandbox Test Mode</span>
                        <select
                          value={paystackInput.testMode ? 'true' : 'false'}
                          onChange={(e) => setPaystackInput({ ...paystackInput, testMode: e.target.value === 'true' })}
                          className="border border-slate-200 rounded-lg p-1.5 bg-white font-bold focus:outline-none text-[11px]"
                        >
                          <option value="true">Test Mode</option>
                          <option value="false">Live Mode</option>
                        </select>
                      </div>

                      <div className="flex items-center justify-between border-t border-slate-100 pt-4.5">
                        <button
                          type="button"
                          onClick={handleVerifyPaystack}
                          disabled={verifyingPaystack}
                          className="px-4 py-2 bg-slate-950 hover:bg-slate-800 text-white font-bold rounded-xl shadow-xs flex items-center space-x-1.5 transition disabled:opacity-50 cursor-pointer text-xs"
                        >
                          {verifyingPaystack ? (
                            <span className="animate-spin rounded-full h-3 w-3 border border-white border-t-transparent" />
                          ) : (
                            <Sparkles className="w-3.5 h-3.5 text-yellow-400" />
                          )}
                          <span>Verify & Enable Paystack</span>
                        </button>

                        <div className="flex items-center space-x-2">
                          <span className="text-[10px] font-bold text-slate-500 uppercase">Module Toggle:</span>
                          <button
                            onClick={() => handleToggleModule('paystack')}
                            disabled={!integrations.paystack.validated}
                            className={`relative inline-flex h-5.5 w-10.5 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none disabled:opacity-40 ${
                              integrations.paystack.enabled ? 'bg-indigo-600' : 'bg-slate-200'
                            }`}
                          >
                            <span className={`pointer-events-none inline-block h-4.5 w-4.5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out ${
                              integrations.paystack.enabled ? 'translate-x-5' : 'translate-x-0'
                            }`} />
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                )}

                {/* 4c. GOOGLE CALENDAR CONNECTION */}
                {settingsSubTab === 'google' && (
                  <div className="space-y-5 max-w-md">
                    <div>
                      <h3 className="text-sm font-extrabold text-slate-800">Google Calendar OAuth Sync</h3>
                      <p className="text-[11px] text-slate-500 mt-1 leading-relaxed">
                        On booking confirmation, dynamically create calendar appointments in your connected primary schedule. Auto-removes on cancellation or reschedule events.
                      </p>
                    </div>

                    <div className="border border-slate-200/60 rounded-3xl p-5 bg-slate-50/30 space-y-4">
                      {integrations.googleCalendar.connected ? (
                        <div className="space-y-3">
                          <div className="flex items-center space-x-3 bg-emerald-50 border border-emerald-150 p-4 rounded-2xl text-emerald-800">
                            <Check className="w-5 h-5 text-emerald-500 flex-shrink-0" />
                            <div>
                              <span className="font-extrabold block text-xs">Connected Successfully!</span>
                              <span className="text-[10px] font-mono mt-1 block">{integrations.googleCalendar.email}</span>
                            </div>
                          </div>
                          
                          <p className="text-[10px] text-slate-400 font-medium">
                            Authorized with full offline scopes. Refresh tokens are stored and dynamically refreshed inside WP transients.
                          </p>

                          <button
                            onClick={() => {
                              onUpdateIntegrations({
                                ...integrations,
                                googleCalendar: {
                                  ...integrations.googleCalendar,
                                  connected: false,
                                  email: '',
                                  validated: false,
                                  enabled: false,
                                }
                              });
                            }}
                            className="px-4 py-2 border border-rose-200 text-rose-600 hover:bg-rose-50 rounded-xl font-bold transition text-xs"
                          >
                            Disconnect Calendar Account
                          </button>
                        </div>
                      ) : (
                        <div className="text-center py-6 space-y-4">
                          <Calendar className="w-10 h-10 text-slate-300 mx-auto" />
                          <div className="space-y-1 text-center">
                            <h4 className="font-extrabold text-slate-700 text-xs">OAuth Client Sync Required</h4>
                            <p className="text-[11px] text-slate-500 max-w-xs mx-auto">
                              Connect your Google account using standard secure OAuth 2.0. No API details exposed.
                            </p>
                          </div>
                          <button
                            onClick={handleConnectGoogle}
                            disabled={verifyingGoogle}
                            className="inline-flex items-center space-x-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-xs transition disabled:opacity-50 cursor-pointer text-xs"
                          >
                            {verifyingGoogle ? (
                              <span className="animate-spin rounded-full h-3.5 w-3.5 border-2 border-white border-t-transparent" />
                            ) : (
                              <CalendarCheck2 className="w-4 h-4 stroke-[2.5]" />
                            )}
                            <span>Connect Google Calendar</span>
                          </button>
                        </div>
                      )}
                    </div>

                    <div className="flex items-center justify-between border-t border-slate-100 pt-4">
                      <span className="text-[10px] font-bold text-slate-500 uppercase">Synchronizer Hook Active:</span>
                      <button
                        onClick={() => handleToggleModule('googleCalendar')}
                        disabled={!integrations.googleCalendar.validated}
                        className={`relative inline-flex h-5.5 w-10.5 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none disabled:opacity-40 ${
                          integrations.googleCalendar.enabled ? 'bg-indigo-600' : 'bg-slate-200'
                        }`}
                      >
                        <span className={`pointer-events-none inline-block h-4.5 w-4.5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out ${
                          integrations.googleCalendar.enabled ? 'translate-x-5' : 'translate-x-0'
                        }`} />
                      </button>
                    </div>
                  </div>
                )}

                {/* 4d. WHATSAPP ALERTS */}
                {settingsSubTab === 'whatsapp' && (
                  <div className="space-y-5 max-w-md">
                    <div>
                      <h3 className="text-sm font-extrabold text-slate-800">WhatsApp Meta Cloud Integration</h3>
                      <p className="text-[11px] text-slate-500 mt-1 leading-relaxed">
                        Send automated pre-approved WhatsApp transactional alerts to customers at appointment triggers.
                      </p>
                    </div>

                    <div className="space-y-3.5">
                      <div>
                        <label className="block font-bold text-slate-500 mb-1.5">WhatsApp System Access Token</label>
                        <input
                          type="password"
                          placeholder="EAABw..."
                          value={whatsappInput.accessToken}
                          onChange={(e) => setWhatsappInput({ ...whatsappInput, accessToken: e.target.value })}
                          className="w-full border border-slate-200 rounded-xl p-2.5 font-mono text-[11px] bg-slate-50/50 focus:outline-none focus:border-indigo-500"
                        />
                      </div>

                      <div className="grid grid-cols-2 gap-3">
                        <div>
                          <label className="block font-bold text-slate-500 mb-1.5">Phone Number ID</label>
                          <input
                            type="text"
                            placeholder="E.g. 10934857203"
                            value={whatsappInput.phoneId}
                            onChange={(e) => setWhatsappInput({ ...whatsappInput, phoneId: e.target.value })}
                            className="w-full border border-slate-200 rounded-xl p-2.5 font-mono text-[11px] bg-slate-50/50 focus:outline-none focus:border-indigo-500"
                          />
                        </div>
                        <div>
                          <label className="block font-bold text-slate-500 mb-1.5">Pre-approved Template ID</label>
                          <input
                            type="text"
                            placeholder="yasmine_booking_receipt"
                            value={whatsappInput.templateName}
                            onChange={(e) => setWhatsappInput({ ...whatsappInput, templateName: e.target.value })}
                            className="w-full border border-slate-200 rounded-xl p-2.5 font-mono text-[11px] bg-slate-50/50 focus:outline-none focus:border-indigo-500"
                          />
                        </div>
                      </div>

                      <div className="flex items-center justify-between border-t border-slate-100 pt-4.5">
                        <button
                          type="button"
                          onClick={handleVerifyWhatsApp}
                          disabled={verifyingWhatsApp}
                          className="px-4 py-2 bg-slate-950 hover:bg-slate-800 text-white font-bold rounded-xl shadow-xs flex items-center space-x-1.5 transition disabled:opacity-50 cursor-pointer text-xs"
                        >
                          {verifyingWhatsApp ? (
                            <span className="animate-spin rounded-full h-3 w-3 border border-white border-t-transparent" />
                          ) : (
                            <Check className="w-3.5 h-3.5" />
                          )}
                          <span>Validate & Lock Meta Connection</span>
                        </button>

                        <div className="flex items-center space-x-2">
                          <span className="text-[10px] font-bold text-slate-500 uppercase">WhatsApp Toggle:</span>
                          <button
                            onClick={() => handleToggleModule('whatsapp')}
                            disabled={!integrations.whatsapp.validated}
                            className={`relative inline-flex h-5.5 w-10.5 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none disabled:opacity-40 ${
                              integrations.whatsapp.enabled ? 'bg-indigo-600' : 'bg-slate-200'
                            }`}
                          >
                            <span className={`pointer-events-none inline-block h-4.5 w-4.5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out ${
                              integrations.whatsapp.enabled ? 'translate-x-5' : 'translate-x-0'
                            }`} />
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                )}

                {/* 4e. TWILIO SMS SETTINGS */}
                {settingsSubTab === 'sms' && (
                  <div className="space-y-5 max-w-md">
                    <div>
                      <h3 className="text-sm font-extrabold text-slate-800">Twilio SMS Configuration</h3>
                      <p className="text-[11px] text-slate-500 mt-1 leading-relaxed">
                        Configure Twilio to dispatch high-priority SMS reminders for appointments. Independent from WhatsApp Meta alerts.
                      </p>
                    </div>

                    <div className="space-y-3.5">
                      <div>
                        <label className="block font-bold text-slate-500 mb-1.5">Twilio Account SID</label>
                        <input
                          type="text"
                          placeholder="AC..."
                          value={twilioInput.accountSid}
                          onChange={(e) => setTwilioInput({ ...twilioInput, accountSid: e.target.value })}
                          className="w-full border border-slate-200 rounded-xl p-2.5 font-mono text-[11px] bg-slate-50/50 focus:outline-none focus:border-indigo-500"
                        />
                      </div>

                      <div>
                        <label className="block font-bold text-slate-500 mb-1.5">Twilio Authentication Token</label>
                        <input
                          type="password"
                          placeholder="Secret Auth Token"
                          value={twilioInput.authToken}
                          onChange={(e) => setTwilioInput({ ...twilioInput, authToken: e.target.value })}
                          className="w-full border border-slate-200 rounded-xl p-2.5 font-mono text-[11px] bg-slate-50/50 focus:outline-none focus:border-indigo-500"
                        />
                      </div>

                      <div>
                        <label className="block font-bold text-slate-500 mb-1.5">Twilio Sender Number (From)</label>
                        <input
                          type="text"
                          placeholder="E.g. +18503848573"
                          value={twilioInput.senderPhone}
                          onChange={(e) => setTwilioInput({ ...twilioInput, senderPhone: e.target.value })}
                          className="w-full border border-slate-200 rounded-xl p-2.5 font-mono text-[11px] bg-slate-50/50 focus:outline-none focus:border-indigo-500"
                        />
                      </div>

                      <div className="flex items-center justify-between border-t border-slate-100 pt-4.5">
                        <button
                          type="button"
                          onClick={handleVerifyTwilio}
                          disabled={verifyingTwilio}
                          className="px-4 py-2 bg-slate-950 hover:bg-slate-800 text-white font-bold rounded-xl shadow-xs flex items-center space-x-1.5 transition disabled:opacity-50 cursor-pointer text-xs"
                        >
                          {verifyingTwilio ? (
                            <span className="animate-spin rounded-full h-3 w-3 border border-white border-t-transparent" />
                          ) : (
                            <Check className="w-3.5 h-3.5" />
                          )}
                          <span>Validate Twilio Credentials</span>
                        </button>

                        <div className="flex items-center space-x-2">
                          <span className="text-[10px] font-bold text-slate-500 uppercase">SMS Toggle:</span>
                          <button
                            onClick={() => handleToggleModule('twilio')}
                            disabled={!integrations.twilio.validated}
                            className={`relative inline-flex h-5.5 w-10.5 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none disabled:opacity-40 ${
                              integrations.twilio.enabled ? 'bg-indigo-600' : 'bg-slate-200'
                            }`}
                          >
                            <span className={`pointer-events-none inline-block h-4.5 w-4.5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out ${
                              integrations.twilio.enabled ? 'translate-x-5' : 'translate-x-0'
                            }`} />
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                )}

                {/* 4f. EMAIL TEMPLATES EDITOR */}
                {settingsSubTab === 'emails' && (
                  <form onSubmit={handleSaveEmailTemplate} className="space-y-4">
                    <div className="flex items-center justify-between border-b border-slate-150 pb-2.5">
                      <h3 className="text-sm font-extrabold text-slate-800">HTML Notification Templates</h3>
                      
                      <div className="flex items-center space-x-1.5">
                        <span className="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Trigger Target:</span>
                        <select
                          value={selectedTemplateKey}
                          onChange={(e: any) => setSelectedTemplateKey(e.target.value)}
                          className="border border-slate-200 rounded-xl p-2 bg-white font-bold text-[11px] focus:outline-none"
                        >
                          <option value="booking_created_customer">Created - Customer Alert</option>
                          <option value="booking_created_admin">Created - Admin Notification</option>
                          <option value="booking_confirmed_customer">Confirmed - Customer Invoice</option>
                          <option value="booking_confirmed_admin">Confirmed - Admin Booking Alert</option>
                          <option value="booking_reminder_customer">Reminder - Client Reminder</option>
                          <option value="booking_cancelled_customer">Cancelled - Client Alert</option>
                          <option value="booking_cancelled_admin">Cancelled - Admin Notification</option>
                          <option value="booking_rescheduled_customer">Rescheduled - Client Alert</option>
                          <option value="balance_due_customer">Invoice - Client Balance Due Request</option>
                        </select>
                      </div>
                    </div>

                    <div className="bg-amber-50/40 border border-amber-150 rounded-2xl p-4 text-[10px] text-amber-800 leading-relaxed font-semibold">
                      <span className="font-extrabold block mb-1">ℹ️ Permitted Template Merge Tags:</span>
                      `{"{{customer_name}}"}`, `{"{{service_name}}"}`, `{"{{booking_date}}"}`, `{"{{booking_time}}"}`, `{"{{deposit_amount}}"}`, `{"{{balance_due}}"}`, `{"{{business_name}}"}`, `{"{{reschedule_link}}"}`, `{"{{cancel_link}}"}`, `{"{{logo_url}}"}`, `{"{{ref_code}}"}`
                    </div>

                    <div className="space-y-4">
                      <div>
                        <label className="block font-bold text-slate-500 mb-1.5">Subject Line</label>
                        <input
                          type="text"
                          required
                          value={emailTemplateForm.subject}
                          onChange={(e) => setEmailTemplateForm({ ...emailTemplateForm, subject: e.target.value })}
                          className="w-full border border-slate-200 rounded-xl p-2.5 bg-slate-50/50 focus:outline-none focus:border-indigo-500 font-bold text-slate-800 text-xs"
                        />
                      </div>

                      <div>
                        <label className="block font-bold text-slate-500 mb-1.5">HTML Message Content Body</label>
                        <textarea
                          required
                          rows={6}
                          value={emailTemplateForm.body}
                          onChange={(e) => setEmailTemplateForm({ ...emailTemplateForm, body: e.target.value })}
                          className="w-full border border-slate-200 rounded-xl p-3 bg-slate-50/50 focus:outline-none focus:border-indigo-500 font-mono text-[11px] leading-relaxed text-slate-800"
                        />
                      </div>

                      <div className="flex justify-end pt-1.5 border-t border-slate-100">
                        <button
                          type="submit"
                          className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-xs transition flex items-center space-x-1.5 cursor-pointer text-xs"
                        >
                          <Save className="w-3.5 h-3.5" />
                          <span>Save Template Trigger</span>
                        </button>
                      </div>
                    </div>
                  </form>
                )}
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
