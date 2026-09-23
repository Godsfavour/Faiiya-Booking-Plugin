import React from 'react';
import { motion } from 'motion/react';
import { 
  Check, ChevronRight, ChevronLeft, Calendar as CalendarIcon, 
  Clock, DollarSign, User, Mail, Phone, FileText, Landmark, CreditCard, Sparkles, MapPin, Download 
} from 'lucide-react';
import { Service, Booking, BusinessSettings, IntegrationsState } from '../types';

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

interface BookingFormSimulatorProps {
  services: Service[];
  bookings: Booking[];
  businessSettings: BusinessSettings;
  integrations: IntegrationsState;
  onSubmitBooking: (bookingData: Omit<Booking, 'id' | 'createdAt' | 'status' | 'referenceCode' | 'depositPaid' | 'balanceDue' | 'balancePaid'>) => Promise<{ id: string; referenceCode: string; depositPaid: number; balanceDue: number; status: 'confirmed' | 'pending_payment' }>;
  onShowToast?: (message: string, type?: 'success' | 'info' | 'warning') => void;
}

export default function BookingFormSimulator({
  services,
  bookings,
  businessSettings,
  integrations,
  onSubmitBooking,
  onShowToast,
}: BookingFormSimulatorProps) {
  const [step, setStep] = React.useState<number>(1);
  const [selectedServiceId, setSelectedServiceId] = React.useState<string>('');
  const [selectedCategoryOptionId, setSelectedCategoryOptionId] = React.useState<string>('');
  const [selectedDate, setSelectedDate] = React.useState<string>('');
  const [selectedTime, setSelectedTime] = React.useState<string>('');
  
  const [customerName, setCustomerName] = React.useState<string>('');
  const [customerEmail, setCustomerEmail] = React.useState<string>('');
  const [customerPhone, setCustomerPhone] = React.useState<string>('');
  const [customerAddress, setCustomerAddress] = React.useState<string>('');
  const [notes, setNotes] = React.useState<string>('');
  
  const [paymentMethod, setPaymentMethod] = React.useState<'paystack' | 'bank_transfer'>('bank_transfer');
  const [paymentChoice, setPaymentChoice] = React.useState<'deposit' | 'full'>('deposit');
  
  // Checkout states
  const [isSubmitting, setIsSubmitting] = React.useState<boolean>(false);
  const [checkoutError, setCheckoutError] = React.useState<string>('');
  const [isProcessingCard, setIsProcessingCard] = React.useState<boolean>(false);
  const [completedBooking, setCompletedBooking] = React.useState<{
    id: string;
    referenceCode: string;
    depositPaid: number;
    balanceDue: number;
    status: 'confirmed' | 'pending_payment';
  } | null>(null);

  // Auto-select first service if list changes and none selected
  React.useEffect(() => {
    if (services.length > 0 && !selectedServiceId) {
      setSelectedServiceId(services[0].id);
    }
  }, [services, selectedServiceId]);

  // Initialize selectedCategoryOptionId if category configuration is enabled, filtered by selected service
  React.useEffect(() => {
    if (businessSettings.categoryConfig?.enabled && businessSettings.categoryConfig.options.length > 0) {
      const currentService = services.find(s => s.id === selectedServiceId) || (selectedServiceId ? null : services[0]);
      if (!currentService) return;

      const assignedIds = currentService.assignedLocationIds;
      
      // Determine locations valid for this service (if empty/none assigned, all locations apply)
      const validOptions = (assignedIds && assignedIds.length > 0)
        ? businessSettings.categoryConfig.options.filter(o => assignedIds.includes(o.id))
        : businessSettings.categoryConfig.options;

      const fallbackOptions = validOptions.length > 0 ? validOptions : businessSettings.categoryConfig.options;
      const isCurrentlySelectedValid = fallbackOptions.some(o => o.id === selectedCategoryOptionId);
      
      // Only change if the current selection is no longer valid for this specific service
      if (!isCurrentlySelectedValid) {
        setSelectedCategoryOptionId(fallbackOptions[0]?.id || '');
      }
    } else {
      setSelectedCategoryOptionId('');
    }
  }, [businessSettings.categoryConfig, selectedCategoryOptionId, selectedServiceId, services]);

  // Set default payment method based on Paystack availability
  React.useEffect(() => {
    if (integrations.paystack.enabled) {
      setPaymentMethod('paystack');
    } else {
      setPaymentMethod('bank_transfer');
    }
  }, [integrations.paystack.enabled]);

  const selectedService = services.find(s => s.id === selectedServiceId) || services[0];

  // Helpers: Calendar integration link generators
  const getGoogleCalendarUrl = () => {
    if (!selectedService || !selectedDate || !selectedTime) return '';
    const startDt = new Date(`${selectedDate}T${selectedTime}`);
    const endDt = new Date(startDt.getTime() + selectedService.duration * 60 * 1000);
    
    // Format to YYYYMMDDTHHMMSSZ (UTC format)
    const formatUTC = (d: Date) => {
      return d.toISOString().replace(/[-:]/g, '').split('.')[0] + 'Z';
    };
    
    const categoryName = businessSettings.categoryConfig?.enabled && selectedCategoryOptionId
      ? businessSettings.categoryConfig.options.find(opt => opt.id === selectedCategoryOptionId)?.name
      : '';
    const titleText = `${selectedService.name}${categoryName ? ` (${categoryName})` : ''} - ${businessSettings.name}`;
    const title = encodeURIComponent(titleText);
    const details = encodeURIComponent(`Booking Reference: ${completedBooking?.referenceCode || ''}\nService: ${selectedService.name}\nAddress: ${customerAddress || 'At Studio'}`);
    const location = encodeURIComponent(businessSettings.address);
    const dates = `${formatUTC(startDt)}/${formatUTC(endDt)}`;
    
    return `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${title}&dates=${dates}&details=${details}&location=${location}`;
  };

  const downloadIcsFile = () => {
    if (!selectedService || !selectedDate || !selectedTime) return;
    const startDt = new Date(`${selectedDate}T${selectedTime}`);
    const endDt = new Date(startDt.getTime() + selectedService.duration * 60 * 1000);
    
    const formatICS = (d: Date) => {
      return d.toISOString().replace(/[-:]/g, '').split('.')[0] + 'Z';
    };
    
    const categoryName = businessSettings.categoryConfig?.enabled && selectedCategoryOptionId
      ? businessSettings.categoryConfig.options.find(opt => opt.id === selectedCategoryOptionId)?.name
      : '';
    const title = `${selectedService.name}${categoryName ? ` (${categoryName})` : ''} - ${businessSettings.name}`;
    const details = `Booking Reference: ${completedBooking?.referenceCode || ''}\\nService: ${selectedService.name}\\nAddress: ${customerAddress || 'At Studio'}`;
    const location = businessSettings.address;
    
    const icsContent = [
      'BEGIN:VCALENDAR',
      'VERSION:2.0',
      'PRODID:-//Yasmine Booking//NONSGML Calendar//EN',
      'BEGIN:VEVENT',
      `UID:${completedBooking?.id || Date.now()}@yasminebooking.com`,
      `DTSTAMP:${formatICS(new Date())}`,
      `DTSTART:${formatICS(startDt)}`,
      `DTEND:${formatICS(endDt)}`,
      `SUMMARY:${title}`,
      `DESCRIPTION:${details}`,
      `LOCATION:${location}`,
      'END:VEVENT',
      'END:VCALENDAR'
    ].join('\r\n');
    
    const blob = new Blob([icsContent], { type: 'text/calendar;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `appointment-${completedBooking?.referenceCode || 'booking'}.ics`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  const symbol = getCurrencySymbol(businessSettings.currency);
  const formatPrice = (value: number) => {
    return `${symbol}${value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
  };

  // Helper: Get available time slots for the chosen date
  const getTimeSlots = () => {
    if (!selectedDate || !selectedService) return [];
    
    const startHour = parseInt(businessSettings.workingHoursStart.split(':')[0]) || 9;
    const endHour = parseInt(businessSettings.workingHoursEnd.split(':')[0]) || 17;
    
    const slots = [];
    const serviceDuration = selectedService.duration;
    
    // Create hourly slots
    for (let hour = startHour; hour < endHour; hour++) {
      const timeString = `${hour.toString().padStart(2, '0')}:00`;
      const slotDateTimeStr = `${selectedDate}T${timeString}:00`;
      
      // Double booking check: does this slot overlap with any active (confirmed/pending) bookings?
      const isBooked = bookings.some(booking => {
        if (booking.status === 'cancelled' || booking.status === 'no_show') return false;
        
        const bookingStart = new Date(booking.dateTime).getTime();
        // Lookup booking service duration
        const bookingService = services.find(s => s.id === booking.serviceId);
        const bookingDuration = bookingService ? bookingService.duration : 60;
        const bookingEnd = bookingStart + bookingDuration * 60 * 1000;
        
        const slotStart = new Date(slotDateTimeStr).getTime();
        const slotEnd = slotStart + serviceDuration * 60 * 1000;
        
        // Overlap conditions
        return (slotStart < bookingEnd && slotEnd > bookingStart);
      });
      
      slots.push({
        time: timeString,
        dateTimeStr: slotDateTimeStr,
        isBooked,
      });
    }
    
    return slots;
  };

  const slots = getTimeSlots();

  // Helper: calculate deposit and balance
  const calculateFees = () => {
    if (!selectedService) return { price: 0, deposit: 0, balance: 0, isFull: false };
    
    // Calculate category pricing adjustment
    const categoryOption = businessSettings.categoryConfig?.enabled 
      ? businessSettings.categoryConfig.options.find(opt => opt.id === selectedCategoryOptionId)
      : null;
    const categoryPrice = categoryOption ? categoryOption.priceValue : 0;
    const price = selectedService.price + categoryPrice;
    
    let deposit = 0;
    if (paymentChoice === 'full') {
      deposit = price;
    } else if (selectedService.depositValue > 0) {
      if (selectedService.depositType === 'percentage') {
        deposit = (price * selectedService.depositValue) / 100;
      } else {
        deposit = selectedService.depositValue;
      }
    } else {
      deposit = price;
    }
    // Round to 2 decimals
    deposit = Math.round(deposit * 100) / 100;
    const balance = Math.round((price - deposit) * 100) / 100;
    return { price, deposit, balance, isFull: paymentChoice === 'full' };
  };

  const { price, deposit, balance, isFull } = calculateFees();

  const handleNextStep = () => {
    setCheckoutError('');
    if (step === 1 && !selectedServiceId) {
      setCheckoutError('Please select a service to proceed.');
      return;
    }
    if (step === 2 && (!selectedDate || !selectedTime)) {
      setCheckoutError('Please pick an available date and time slot.');
      return;
    }
    if (step === 3) {
      if (!customerName || !customerEmail || !customerPhone || !customerAddress) {
        setCheckoutError('Please complete all required fields.');
        return;
      }
      // Simple email validation
      if (!customerEmail.includes('@') || !customerEmail.includes('.')) {
        setCheckoutError('Please enter a valid email address.');
        return;
      }
    }

    if (step === 1) {
      onShowToast?.('Service selected! Choose your preferred schedule.', 'info');
    } else if (step === 2) {
      onShowToast?.('Date & time slot locked! Enter your details.', 'info');
    } else if (step === 3) {
      onShowToast?.('Contact details saved! Review deposit and payment method.', 'info');
    }

    setStep(prev => prev + 1);
  };

  const handlePrevStep = () => {
    setCheckoutError('');
    setStep(prev => prev - 1);
  };

  const handleFormSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setCheckoutError('');
    setIsSubmitting(true);

    const bookingDateTime = `${selectedDate}T${selectedTime}:00`;

    // Double check slot availability again on submission (Simulating server-side safety check)
    const doubleBooked = bookings.some(booking => {
      if (booking.status === 'cancelled' || booking.status === 'no_show') return false;
      const bStart = new Date(booking.dateTime).getTime();
      const bService = services.find(s => s.id === booking.serviceId);
      const bDuration = bService ? bService.duration : 60;
      const bEnd = bStart + bDuration * 60 * 1000;
      
      const sStart = new Date(bookingDateTime).getTime();
      const sEnd = sStart + selectedService.duration * 60 * 1000;
      
      return (sStart < bEnd && sEnd > bStart);
    });

    if (doubleBooked) {
      setCheckoutError('This slot was just booked by another client. Please return to step 2 and choose another slot.');
      setStep(2);
      setIsSubmitting(false);
      return;
    }

    try {
      if (paymentMethod === 'paystack') {
        // Simulate Card payment processing
        setIsProcessingCard(true);
        // Wait 1.5s for "processing gate"
        await new Promise(resolve => setTimeout(resolve, 1500));
        setIsProcessingCard(false);
      }

      // Submit booking
      const res = await onSubmitBooking({
        serviceId: selectedServiceId,
        customerName,
        customerEmail,
        customerPhone,
        customerAddress,
        dateTime: bookingDateTime,
        paymentMethod,
        notes,
        categoryOptionId: selectedCategoryOptionId || undefined,
      });

      setCompletedBooking(res);
      setStep(5);
      onShowToast?.(`✨ Appointment successfully scheduled! Reference: ${res.referenceCode}`, 'success');
    } catch (err: any) {
      setCheckoutError(err.message || 'Error creating appointment.');
    } finally {
      setIsSubmitting(false);
    }
  };

  const resetSimulator = () => {
    setStep(1);
    setSelectedDate('');
    setSelectedTime('');
    setCustomerName('');
    setCustomerEmail('');
    setCustomerPhone('');
    setCustomerAddress('');
    setNotes('');
    setCompletedBooking(null);
    setCheckoutError('');
    onShowToast?.('Form reset for new appointment booking.', 'info');
  };

  // Convert hex color to semi-transparent style
  const brandColorHex = businessSettings.brandColor || '#10B981';
  const inlineBrandStyle = {
    '--brand-primary': brandColorHex,
    '--brand-primary-light': `${brandColorHex}15`,
    '--brand-primary-border': `${brandColorHex}30`,
  } as React.CSSProperties;

  return (
    <div id="booking-widget-container" className="flex flex-col h-full bg-white border border-slate-200/60 rounded-3xl overflow-hidden shadow-xs font-sans" style={inlineBrandStyle}>
      {/* Visual Indicator of Shortcode */}
      <div id="booking-widget-topbar" className="bg-slate-50/50 border-b border-slate-150 px-5 py-3.5 flex items-center justify-between">
        <div className="flex items-center space-x-2.5">
          <span className="bg-indigo-50 text-indigo-700 text-[10px] font-mono font-bold px-2 py-0.5 rounded-full border border-indigo-100">
            [yasmine_booking_form]
          </span>
          <span className="text-xs font-bold text-slate-500">Live Shortcode Form Widget</span>
        </div>
        <div className="flex space-x-1.5">
          <span className="w-2.5 h-2.5 rounded-full bg-rose-400" />
          <span className="w-2.5 h-2.5 rounded-full bg-amber-400" />
          <span className="w-2.5 h-2.5 rounded-full bg-emerald-400" />
        </div>
      </div>

      {/* Multi-step indicators with Horizontal Stepper, Arrows & Remaining Steps */}
      {step < 5 && (
        <div id="booking-stepper-header" className="border-b border-slate-200/80 bg-slate-50/70 p-4 space-y-3">
          {/* Horizontal Stepper wrapped into unit items so arrows never wrap alone */}
          <div className="flex items-center flex-wrap gap-x-2 gap-y-2.5">
            {[
              { n: 1, name: 'Service & Location' },
              { n: 2, name: 'Date & Time' },
              { n: 3, name: 'Client Details' },
              { n: 4, name: 'Confirm & Pay' }
            ].map((s, idx) => (
              <div key={s.n} className="inline-flex items-center gap-2 whitespace-nowrap">
                <div 
                  className={`inline-flex items-center space-x-2 text-xs font-semibold transition px-2.5 py-1.5 rounded-xl ${
                    step === s.n 
                      ? 'bg-white text-[var(--brand-primary)] shadow-xs ring-1 ring-slate-200 font-extrabold' 
                      : step > s.n 
                      ? 'text-emerald-700 bg-emerald-50/80 font-bold' 
                      : 'text-slate-400'
                  }`}
                >
                  <span className={`w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold ${
                    step === s.n 
                      ? 'bg-[var(--brand-primary)] text-white shadow-xs' 
                      : step > s.n 
                      ? 'bg-emerald-600 text-white' 
                      : 'bg-slate-200 text-slate-500'
                  }`}>
                    {step > s.n ? <Check className="w-3 h-3 stroke-[2.5]" /> : s.n}
                  </span>
                  <span>{s.name}</span>
                </div>
                {idx < 3 && (
                  <ChevronRight className={`w-3.5 h-3.5 flex-shrink-0 transition-colors ${
                    step > s.n ? 'text-emerald-600' : step === s.n ? 'text-[var(--brand-primary)]' : 'text-slate-300'
                  }`} />
                )}
              </div>
            ))}
          </div>

          {/* Stepper Status Bar (Line 1: Breadcrumbs, Line 2: Current Step Title Bold) */}
          <div className="pt-2.5 border-t border-slate-200/80 space-y-1">
            {/* Line 1: Breadcrumbs */}
            <div className="flex items-center flex-wrap gap-2 text-xs">
              <span className="bg-indigo-100 text-indigo-800 font-extrabold px-2.5 py-0.5 rounded-full text-[11px]">
                Step {step} of 4
              </span>
              <span className="text-slate-400 text-xs">•</span>
              <span className="bg-amber-50 text-amber-800 border border-amber-200 font-bold px-2.5 py-0.5 rounded-full text-[10px]">
                {4 - step > 0 ? `${4 - step} step${4 - step > 1 ? 's' : ''} remaining` : 'Final Step'}
              </span>
              {step < 4 && (
                <>
                  <span className="text-slate-400 text-xs">•</span>
                  <span className="text-slate-500 font-medium text-xs">
                    {step === 1 ? 'Next: Date & Time →' : step === 2 ? 'Next: Enter Details →' : 'Next: Review & Payment →'}
                  </span>
                </>
              )}
            </div>

            {/* Line 2: Current Step Title (Bold) */}
            <div className="pt-0.5">
              <h3 className="text-base font-extrabold text-slate-800 tracking-tight">
                {step === 1 ? 'Select Your Service & Service Location' : step === 2 ? 'Choose Appointment Date & Time' : step === 3 ? 'Client Contact & Service Address' : 'Confirm & Pay Deposit'}
              </h3>
            </div>
          </div>
        </div>
      )}

      {/* Main Body */}
      <div id="booking-body" className="flex-1 overflow-y-auto p-6 bg-slate-50/30 min-h-[350px]">
        {checkoutError && (
          <div className="mb-5 p-4 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-2xl flex items-center space-x-2 shadow-xs">
            <span className="font-semibold text-sm">⚠️</span>
            <span>{checkoutError}</span>
          </div>
        )}

        {/* STEP 1: Select Service */}
        {step === 1 && (
          <div id="step-1-service-select" className="space-y-4">
            <p className="text-xs text-slate-500">Choose the appointment category and beauty service for your session.</p>

            {/* Redesigned Category Selector Tabs with "All Services" at the End */}
            <div className="flex items-center space-x-2 overflow-x-auto pb-1 scrollbar-none">
              {['Hair', 'Spa & Massage', 'Makeup', 'all'].map((catKey) => {
                const isAll = catKey === 'all';
                const label = isAll ? 'All Services' : catKey;
                const isSelected = isAll 
                  ? (!selectedService || selectedService.name.length > 0)
                  : (selectedService && selectedService.name.toLowerCase().includes(catKey.toLowerCase().split(' ')[0]));

                return (
                  <button
                    key={catKey}
                    type="button"
                    onClick={() => {
                      if (isAll) {
                        onShowToast?.('Showing All Services', 'info');
                      } else {
                        const match = services.find(s => s.name.toLowerCase().includes(catKey.toLowerCase().split(' ')[0]));
                        if (match) setSelectedServiceId(match.id);
                      }
                    }}
                    className={`px-3.5 py-1.5 rounded-full text-xs font-bold transition whitespace-nowrap cursor-pointer border ${
                      isSelected && !isAll
                        ? 'bg-[var(--brand-primary)] text-white border-[var(--brand-primary)] shadow-xs'
                        : isAll
                        ? 'bg-slate-100 text-slate-700 hover:bg-slate-200 border-slate-200'
                        : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'
                    }`}
                  >
                    {label}
                  </button>
                );
              })}
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-3.5">
              {services.map((s) => {
                // Calculate dynamic adjusted service card price
                const categoryOption = businessSettings.categoryConfig?.enabled 
                  ? businessSettings.categoryConfig.options.find(opt => opt.id === selectedCategoryOptionId)
                  : null;
                const categoryPrice = categoryOption ? categoryOption.priceValue : 0;
                const sPrice = s.price + categoryPrice;

                let cardDepositValue = s.depositValue;
                if (s.depositValue > 0 && s.depositType === 'percentage') {
                  cardDepositValue = (sPrice * s.depositValue) / 100;
                }

                return (
                  <motion.label 
                    whileTap={{ scale: 0.98 }}
                    key={s.id}
                    onClick={() => {
                      setSelectedServiceId(s.id);
                      onShowToast?.(`Selected service: ${s.name}`, 'info');
                    }}
                    className={`flex flex-col justify-between border rounded-2xl p-4.5 cursor-pointer transition relative overflow-hidden duration-200 shadow-sm hover:shadow-md ${
                      selectedServiceId === s.id 
                        ? 'bg-white border-[var(--brand-primary)] ring-2 ring-[var(--brand-primary)]/20 shadow-md' 
                        : 'bg-white border-slate-200/70 hover:border-slate-300'
                    }`}
                  >
                    {s.imageUrl && (
                      <div className="w-full h-36 rounded-xl overflow-hidden mb-3.5 bg-slate-100 border border-slate-100">
                        <img 
                          src={s.imageUrl} 
                          alt={s.name} 
                          loading="lazy" 
                          className="w-full h-full object-cover transition-transform duration-300 hover:scale-105"
                        />
                      </div>
                    )}

                    <div className="flex-1">
                      <div className="flex justify-between items-start gap-2">
                        <h3 className="text-sm font-bold text-slate-900 leading-tight">{s.name}</h3>
                        <span className="text-sm font-extrabold text-[var(--brand-primary)] whitespace-nowrap">
                          {formatPrice(sPrice)}
                        </span>
                      </div>
                      <p className="text-xs text-slate-500 mt-1 line-clamp-2 leading-relaxed">{s.description}</p>
                    </div>

                    {/* Assigned Locations visible on frontend service card */}
                    {businessSettings.categoryConfig?.enabled && (
                      <div className="mt-2.5 pt-2 border-t border-slate-100 flex items-center flex-wrap gap-1">
                        <span className="text-[10px] font-bold text-slate-400 flex items-center gap-0.5 mr-0.5">
                          <MapPin className="w-3 h-3 text-indigo-500" />
                          Locations:
                        </span>
                        {(!s.assignedLocationIds || s.assignedLocationIds.length === 0) ? (
                          <span className="text-[10px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200/80 px-2 py-0.5 rounded-full">
                            All locations active
                          </span>
                        ) : (
                          s.assignedLocationIds.map(locId => {
                            const loc = businessSettings.categoryConfig?.options.find(o => o.id === locId);
                            return (
                              <span key={locId} className="text-[10px] font-semibold text-indigo-700 bg-indigo-50 border border-indigo-200/80 px-2 py-0.5 rounded-full">
                                {loc ? loc.name : locId}
                              </span>
                            );
                          })
                        )}
                      </div>
                    )}

                    <div className="flex items-center justify-between mt-2.5 pt-2 border-t border-slate-100 text-[11px] text-slate-500">
                      <span>⏱ {s.duration} mins</span>
                      {s.depositValue > 0 ? (
                        <span className="bg-amber-50 text-amber-800 font-bold px-2 py-0.5 rounded-full border border-amber-200 text-[10px]">
                          Deposit: {s.depositType === 'percentage' ? `${s.depositValue}%` : formatPrice(s.depositValue)}
                        </span>
                      ) : (
                        <span className="text-emerald-700 font-semibold text-[10px]">Pay in Full</span>
                      )}
                    </div>
                  </motion.label>
                );
              })}
            </div>

            {/* Dynamic Category Option Selection (e.g. Location) - Filtered by assigned locations of selected service */}
            {businessSettings.categoryConfig?.enabled && businessSettings.categoryConfig.options.length > 0 && (() => {
              const currentService = services.find(s => s.id === selectedServiceId) || services[0];
              const assignedIds = currentService?.assignedLocationIds;
              const availableLocations = (assignedIds && assignedIds.length > 0)
                ? businessSettings.categoryConfig.options.filter(opt => assignedIds.includes(opt.id))
                : businessSettings.categoryConfig.options;

              return (
                <div id="widget-category-selector" className="bg-white border border-slate-200 p-4.5 rounded-2xl space-y-2.5 shadow-sm">
                  <div className="flex justify-between items-center">
                    <span className="text-xs font-bold text-slate-700 tracking-wide uppercase">
                      Select {businessSettings.categoryConfig.label || 'Location'}:
                    </span>
                    <span className="bg-indigo-50 text-indigo-700 text-[9px] font-bold px-2 py-0.5 rounded-full border border-indigo-100">
                      {assignedIds && assignedIds.length > 0 ? `${availableLocations.length} Available for this Service` : 'Required Selection'}
                    </span>
                  </div>
                  
                  {availableLocations.length === 0 ? (
                    <p className="text-xs text-amber-600 bg-amber-50 p-2.5 rounded-xl border border-amber-200">
                      No specific locations linked to this service offering.
                    </p>
                  ) : (
                    <div className="grid grid-cols-2 sm:grid-cols-3 gap-2">
                      {availableLocations.map((opt) => (
                        <motion.button
                          whileTap={{ scale: 0.94 }}
                          key={opt.id}
                          type="button"
                          onClick={() => {
                            setSelectedCategoryOptionId(opt.id);
                            onShowToast?.(`Selected ${businessSettings.categoryConfig.label || 'Category'}: ${opt.name}`, 'info');
                          }}
                          className={`px-3 py-2.5 text-xs font-bold rounded-xl border text-center transition duration-150 cursor-pointer shadow-xs ${
                            selectedCategoryOptionId === opt.id
                              ? 'bg-[var(--brand-primary)] text-white border-[var(--brand-primary)] shadow-sm'
                              : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'
                          }`}
                        >
                          <div className="truncate">{opt.name}</div>
                          <div className={`text-[9px] mt-0.5 font-medium ${selectedCategoryOptionId === opt.id ? 'text-white/90' : 'text-slate-400'}`}>
                            {opt.priceValue === 0 ? 'No fee' : `+${symbol}${opt.priceValue.toLocaleString()}`}
                          </div>
                        </motion.button>
                      ))}
                    </div>
                  )}
                </div>
              );
            })()}

            <div className="flex justify-end pt-3">
              <motion.button
                whileTap={{ scale: 0.94 }}
                type="button"
                onClick={handleNextStep}
                className="flex items-center space-x-1.5 px-5 py-3 bg-[var(--brand-primary)] hover:opacity-95 text-white text-xs font-bold rounded-2xl transition shadow-sm ml-auto cursor-pointer"
              >
                <span>Select Date &amp; Time</span>
                <ChevronRight className="w-4 h-4" />
              </motion.button>
            </div>
          </div>
        )}

        {/* STEP 2: Pick Schedule with Calendar */}
        {step === 2 && (
          <div id="step-2-schedule" className="space-y-4">
            <p className="text-xs text-slate-500">Choose your date from the interactive calendar and pick an available time slot.</p>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {/* Interactive Month Calendar */}
              <div className="bg-white border border-slate-200/90 rounded-2xl p-4 shadow-sm space-y-3">
                <div className="flex items-center justify-between border-b border-slate-100 pb-2.5">
                  <span className="text-xs font-bold text-slate-800">
                    {new Date().toLocaleDateString(undefined, { month: 'long', year: 'numeric' })}
                  </span>
                  <div className="flex space-x-1">
                    <span className="text-[10px] font-bold bg-slate-100 text-slate-600 px-2 py-0.5 rounded-md">Live Month</span>
                  </div>
                </div>

                {/* Calendar Days of Week */}
                <div className="grid grid-cols-7 gap-1 text-center text-[10px] font-bold text-slate-400 pb-1">
                  <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                </div>

                {/* Days Grid with light color, subtle borders, hover & select effect */}
                <div className="grid grid-cols-7 gap-1.5">
                  {Array.from({ length: 35 }).map((_, idx) => {
                    const today = new Date();
                    const dayNum = idx - 2; // Offset for demo month alignment
                    const isDayValid = dayNum >= 1 && dayNum <= 30;
                    const isPast = dayNum < today.getDate();
                    
                    const dateStr = isDayValid 
                      ? `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(dayNum).padStart(2, '0')}`
                      : '';
                    const isSelected = selectedDate === dateStr && isDayValid;

                    if (!isDayValid) {
                      return <div key={idx} className="h-8 rounded-lg opacity-10 bg-slate-100" />;
                    }

                    return (
                      <button
                        key={idx}
                        type="button"
                        disabled={isPast}
                        onClick={() => {
                          setSelectedDate(dateStr);
                          setSelectedTime('');
                          onShowToast?.(`Selected date: ${dateStr}`, 'info');
                        }}
                        className={`h-8 rounded-lg text-xs font-semibold flex items-center justify-center transition-all duration-150 cursor-pointer ${
                          isSelected
                            ? 'bg-[var(--brand-primary)] text-white font-bold border border-[var(--brand-primary)] shadow-sm -translate-y-0.5 scale-[1.02]'
                            : isPast
                            ? 'text-slate-300 cursor-not-allowed line-through bg-slate-50/50 border border-slate-100'
                            : 'bg-white text-slate-700 border border-slate-200/90 hover:bg-slate-50 hover:border-slate-400 hover:-translate-y-0.5 hover:shadow-xs'
                        }`}
                      >
                        {dayNum}
                      </button>
                    );
                  })}
                </div>

                {selectedDate && (
                  <div className="pt-2 border-t border-slate-150 text-[11px] text-emerald-700 font-bold flex items-center space-x-1.5">
                    <Check className="w-3.5 h-3.5" />
                    <span>Selected Date: {selectedDate}</span>
                  </div>
                )}
              </div>

              {/* Time Slots */}
              <div className="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm space-y-3">
                <label className="block text-xs font-bold text-slate-700">Available Time Slots</label>
                {!selectedDate ? (
                  <div className="h-44 border border-dashed border-slate-200 rounded-xl flex items-center justify-center text-xs text-slate-400 text-center p-4">
                    Please select a date from the calendar to view available slots.
                  </div>
                ) : slots.length === 0 ? (
                  <div className="h-44 border border-dashed border-slate-200 rounded-xl flex items-center justify-center text-xs text-slate-400 text-center p-4">
                    No operating slots found for this day.
                  </div>
                ) : (
                  <div className="grid grid-cols-2 gap-2 max-h-52 overflow-y-auto pr-1">
                    {slots.map((slot) => (
                      <motion.button
                        whileTap={{ scale: 0.94 }}
                        key={slot.time}
                        type="button"
                        disabled={slot.isBooked}
                        onClick={() => {
                          setSelectedTime(slot.time);
                          onShowToast?.(`Selected time slot: ${slot.time}`, 'info');
                        }}
                        className={`py-2.5 px-3 text-xs font-bold border rounded-xl transition duration-150 text-center cursor-pointer shadow-xs ${
                          slot.isBooked
                            ? 'bg-slate-100 text-slate-300 border-slate-100 cursor-not-allowed line-through'
                            : selectedTime === slot.time
                            ? 'bg-[var(--brand-primary)] text-white border-[var(--brand-primary)] font-bold shadow-xs'
                            : 'bg-white text-slate-700 border-slate-200 hover:border-slate-300 hover:bg-slate-50'
                        }`}
                      >
                        {slot.time} {slot.isBooked && '(Booked)'}
                      </motion.button>
                    ))}
                  </div>
                )}

                <div className="bg-sky-50 border border-sky-100 rounded-xl p-3 text-[10px] text-sky-850">
                  <span className="font-bold">Operating Hours:</span> {businessSettings.workingHoursStart} - {businessSettings.workingHoursEnd} ({businessSettings.timezone})
                </div>
              </div>
            </div>

            <div className="flex justify-between items-center pt-3 border-t border-slate-150">
              <motion.button
                whileTap={{ scale: 0.94 }}
                type="button"
                onClick={handlePrevStep}
                className="flex items-center space-x-1.5 px-4.5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-2xl transition cursor-pointer"
              >
                <ChevronLeft className="w-4 h-4" />
                <span>Service</span>
              </motion.button>
              <motion.button
                whileTap={{ scale: 0.94 }}
                type="button"
                onClick={handleNextStep}
                className="flex items-center space-x-1.5 px-5 py-3 bg-[var(--brand-primary)] hover:opacity-95 text-white text-xs font-bold rounded-2xl transition shadow-sm cursor-pointer"
              >
                <span>Your Details</span>
                <ChevronRight className="w-4 h-4" />
              </motion.button>
            </div>
          </div>
        )}

        {/* STEP 3: Customer Details */}
        {step === 3 && (
          <div id="step-3-details" className="space-y-4">
            <p className="text-xs text-slate-500">Provide contact information for appointment alerts and tracking.</p>

            <div className="space-y-3.5">
              <div>
                <label className="block text-xs font-bold text-slate-600 mb-1">Full Name *</label>
                <div className="relative">
                  <span className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <User className="w-4 h-4" />
                  </span>
                  <input
                    type="text"
                    required
                    value={customerName}
                    onChange={(e) => setCustomerName(e.target.value)}
                    placeholder="Jane Doe"
                    className="w-full border border-slate-200/60 rounded-2xl pl-10 p-3 text-xs text-slate-800 bg-white focus:outline-none focus:border-[var(--brand-primary)] focus:ring-1 focus:ring-[var(--brand-primary)]/10"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-600 mb-1">Email Address *</label>
                <div className="relative">
                  <span className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <Mail className="w-4 h-4" />
                  </span>
                  <input
                    type="email"
                    required
                    value={customerEmail}
                    onChange={(e) => setCustomerEmail(e.target.value)}
                    placeholder="jane@example.com"
                    className="w-full border border-slate-200/60 rounded-2xl pl-10 p-3 text-xs text-slate-800 bg-white focus:outline-none focus:border-[var(--brand-primary)] focus:ring-1 focus:ring-[var(--brand-primary)]/10"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-600 mb-1">Phone / WhatsApp Number *</label>
                <div className="relative">
                  <span className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <Phone className="w-4 h-4" />
                  </span>
                  <input
                    type="tel"
                    required
                    value={customerPhone}
                    onChange={(e) => setCustomerPhone(e.target.value)}
                    placeholder="+2348031234567 (with country code)"
                    className="w-full border border-slate-200/60 rounded-2xl pl-10 p-3 text-xs text-slate-800 bg-white focus:outline-none focus:border-[var(--brand-primary)] focus:ring-1 focus:ring-[var(--brand-primary)]/10"
                  />
                </div>
                <p className="text-[10px] text-slate-400 mt-1.5 leading-normal">Please include country code without spaces (e.g. +234 or +1) for SMS/WhatsApp sync.</p>
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-600 mb-1">Residential Address *</label>
                <div className="relative">
                  <span className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <MapPin className="w-4 h-4" />
                  </span>
                  <input
                    type="text"
                    required
                    value={customerAddress}
                    onChange={(e) => setCustomerAddress(e.target.value)}
                    placeholder="Enter your complete street address, city"
                    className="w-full border border-slate-200/60 rounded-2xl pl-10 p-3 text-xs text-slate-800 bg-white focus:outline-none focus:border-[var(--brand-primary)] focus:ring-1 focus:ring-[var(--brand-primary)]/10"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-600 mb-1">Special Requests (Optional)</label>
                <div className="relative">
                  <span className="absolute top-3 left-3.5 pointer-events-none text-slate-400">
                    <FileText className="w-4 h-4" />
                  </span>
                  <textarea
                    value={notes}
                    onChange={(e) => setNotes(e.target.value)}
                    placeholder="Any preferences or health considerations..."
                    rows={2}
                    className="w-full border border-slate-200/60 rounded-2xl pl-10 p-3 text-xs text-slate-800 bg-white focus:outline-none focus:border-[var(--brand-primary)] focus:ring-1 focus:ring-[var(--brand-primary)]/10"
                  />
                </div>
              </div>
            </div>

            <div className="flex justify-between items-center pt-3 border-t border-slate-100">
              <motion.button
                whileTap={{ scale: 0.94 }}
                type="button"
                onClick={handlePrevStep}
                className="flex items-center space-x-1.5 px-4.5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-2xl transition duration-150 cursor-pointer"
              >
                <ChevronLeft className="w-4 h-4" />
                <span>Schedule</span>
              </motion.button>
              <motion.button
                whileTap={{ scale: 0.94 }}
                type="button"
                onClick={handleNextStep}
                className="flex items-center space-x-1.5 px-5 py-3 bg-[var(--brand-primary)] hover:opacity-95 text-white text-xs font-bold rounded-2xl transition shadow-sm cursor-pointer"
              >
                <span>Payment Options</span>
                <ChevronRight className="w-4 h-4" />
              </motion.button>
            </div>
          </div>
        )}

        {/* STEP 4: Checkout Payment */}
        {step === 4 && (
          <form id="checkout-form" onSubmit={handleFormSubmit} className="space-y-4">
            <p className="text-xs text-slate-500">Verify your booking details and make a secure payment.</p>

            {/* Summary Ticket */}
            <div className="bg-white border border-slate-200/60 rounded-2xl p-5 space-y-4 shadow-xs">
              <div className="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                  <h4 className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Selected Service</h4>
                  <p className="text-sm font-extrabold text-slate-800 mt-0.5">{selectedService.name}</p>
                </div>
                <span className="text-xs font-bold text-slate-700 bg-slate-50 border border-slate-200/60 px-3 py-1.5 rounded-xl shadow-xs">
                  {selectedService.duration} min
                </span>
              </div>

              {/* Category details row */}
              {businessSettings.categoryConfig?.enabled && selectedCategoryOptionId && (
                <div className="flex justify-between items-center text-xs border-b border-slate-100 pb-2">
                  <span className="text-slate-400 font-bold uppercase text-[10px]">{businessSettings.categoryConfig.label || 'Category'} Selected:</span>
                  <span className="font-bold text-slate-800 bg-slate-100 border border-slate-200 px-2.5 py-1 rounded-lg">
                    {businessSettings.categoryConfig.options.find(opt => opt.id === selectedCategoryOptionId)?.name}
                  </span>
                </div>
              )}

              <div className="flex items-center space-x-4 text-xs text-slate-600">
                <div className="flex items-center space-x-1.5">
                  <CalendarIcon className="w-3.5 h-3.5 text-slate-400" />
                  <span className="font-semibold">{new Date(selectedDate).toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' })}</span>
                </div>
                <div className="flex items-center space-x-1.5">
                  <Clock className="w-3.5 h-3.5 text-slate-400" />
                  <span className="font-semibold">{selectedTime}</span>
                </div>
              </div>

              {/* Price Breakdown */}
              <div className="border-t border-slate-100 pt-3 space-y-2 text-xs text-slate-600">
                <div className="flex justify-between">
                  <span>Total Cost:</span>
                  <span className="font-bold text-slate-800">{formatPrice(price)}</span>
                </div>
                <div className="flex justify-between text-[var(--brand-primary)] font-bold">
                  <span>{isFull ? 'Amount Payable Now (Full):' : 'Deposit Due Now:'}</span>
                  <span>{formatPrice(deposit)}</span>
                </div>
                <div className="flex justify-between text-[11px] text-slate-500 border-t border-dashed border-slate-100 pt-2">
                  <span>Balance Due on Site:</span>
                  <span className={isFull ? 'text-emerald-600 font-bold' : ''}>
                    {isFull ? '₦0.00 (Fully Settled)' : formatPrice(balance)}
                  </span>
                </div>
              </div>
            </div>

            {/* Payment Amount Choice: Deposit vs Pay Full */}
            <div className="space-y-2">
              <label className="block text-xs font-bold text-slate-600">Payment Amount Option</label>
              <div className="grid grid-cols-2 gap-2.5">
                <motion.label
                  whileTap={{ scale: 0.98 }}
                  onClick={() => {
                    setPaymentChoice('deposit');
                    onShowToast?.('Selected: Pay Required Deposit', 'info');
                  }}
                  className={`flex items-start p-3.5 border rounded-2xl cursor-pointer transition ${
                    paymentChoice === 'deposit'
                      ? 'border-[var(--brand-primary)] bg-[var(--brand-primary-light)] ring-1 ring-[var(--brand-primary)]/10'
                      : 'border-slate-200/60 bg-white hover:border-slate-300'
                  }`}
                >
                  <input
                    type="radio"
                    name="payment_choice"
                    checked={paymentChoice === 'deposit'}
                    onChange={() => setPaymentChoice('deposit')}
                    className="mt-0.5 accent-[var(--brand-primary)]"
                  />
                  <div className="ml-2.5">
                    <span className="text-xs font-bold text-slate-800 block">Pay Deposit</span>
                    <span className="text-[11px] text-slate-500 block mt-0.5 leading-snug">Reserve slot now, pay balance at appointment</span>
                    <span className="text-xs font-bold text-emerald-700 block mt-1.5">{formatPrice(selectedService.depositValue > 0 ? (selectedService.depositType === 'percentage' ? (price * selectedService.depositValue / 100) : selectedService.depositValue) : price)}</span>
                  </div>
                </motion.label>

                <motion.label
                  whileTap={{ scale: 0.98 }}
                  onClick={() => {
                    setPaymentChoice('full');
                    onShowToast?.('Selected: Pay Full Balance Upfront', 'info');
                  }}
                  className={`flex items-start p-3.5 border rounded-2xl cursor-pointer transition ${
                    paymentChoice === 'full'
                      ? 'border-[var(--brand-primary)] bg-[var(--brand-primary-light)] ring-1 ring-[var(--brand-primary)]/10'
                      : 'border-slate-200/60 bg-white hover:border-slate-300'
                  }`}
                >
                  <input
                    type="radio"
                    name="payment_choice"
                    checked={paymentChoice === 'full'}
                    onChange={() => setPaymentChoice('full')}
                    className="mt-0.5 accent-[var(--brand-primary)]"
                  />
                  <div className="ml-2.5">
                    <span className="text-xs font-bold text-slate-800 block">Pay in Full</span>
                    <span className="text-[11px] text-slate-500 block mt-0.5 leading-snug">100% upfront settlement with zero balance on visit</span>
                    <span className="text-xs font-bold text-emerald-700 block mt-1.5">{formatPrice(price)}</span>
                  </div>
                </motion.label>
              </div>
            </div>

            {/* Payment Method Choice */}
            <div className="space-y-2.5">
              <label className="block text-xs font-bold text-slate-600">Select Booking Method</label>
              
              <div className="space-y-2.5">
                {integrations.paystack.enabled ? (
                  <motion.label 
                    whileTap={{ scale: 0.98 }}
                    onClick={() => {
                      setPaymentMethod('paystack');
                      onShowToast?.('Selected Paystack Online Payment', 'info');
                    }}
                    className={`flex items-start border rounded-2xl p-4 cursor-pointer transition duration-150 ${
                      paymentMethod === 'paystack' 
                        ? 'border-[var(--brand-primary)] bg-[var(--brand-primary-light)] ring-1 ring-[var(--brand-primary)]/10' 
                        : 'border-slate-200/60 bg-white hover:border-slate-300'
                    }`}
                  >
                    <input
                      type="radio"
                      name="payment_opt"
                      checked={paymentMethod === 'paystack'}
                      onChange={() => setPaymentMethod('paystack')}
                      className="mt-1 accent-[var(--brand-primary)]"
                    />
                    <div className="ml-3.5">
                      <div className="flex items-center space-x-1.5">
                        <CreditCard className="w-4 h-4 text-indigo-600" />
                        <span className="text-xs font-bold text-slate-800">Paystack Card/Bank Checkout</span>
                      </div>
                      <p className="text-[11px] text-slate-500 mt-0.5 leading-normal">Pay dynamic deposit securely online. Booking is auto-confirmed instantly.</p>
                    </div>
                  </motion.label>
                ) : (
                  <div className="p-4 bg-slate-50 border border-slate-200/60 rounded-2xl text-[11px] text-slate-500 leading-relaxed shadow-xs">
                    ℹ️ Paystack card integration is disabled. Enable and enter credentials in WP Settings to offer online checkout.
                  </div>
                )}

                <motion.label 
                  whileTap={{ scale: 0.98 }}
                  onClick={() => {
                    setPaymentMethod('bank_transfer');
                    onShowToast?.('Selected Manual Bank Wire / Transfer', 'info');
                  }}
                  className={`flex items-start border rounded-2xl p-4 cursor-pointer transition duration-150 ${
                    paymentMethod === 'bank_transfer' 
                      ? 'border-[var(--brand-primary)] bg-[var(--brand-primary-light)] ring-1 ring-[var(--brand-primary)]/10' 
                      : 'border-slate-200/60 bg-white hover:border-slate-300'
                  }`}
                >
                  <input
                    type="radio"
                    name="payment_opt"
                    checked={paymentMethod === 'bank_transfer'}
                    onChange={() => setPaymentMethod('bank_transfer')}
                    className="mt-1 accent-[var(--brand-primary)]"
                  />
                  <div className="ml-3.5">
                    <div className="flex items-center space-x-1.5">
                      <Landmark className="w-4 h-4 text-indigo-600" />
                      <span className="text-xs font-bold text-slate-800">Manual Bank Wire / Transfer</span>
                    </div>
                    <p className="text-[11px] text-slate-500 mt-0.5 leading-normal">Wire deposit directly. We hold the slot. Booking confirmed upon manual verification.</p>
                  </div>
                </motion.label>
              </div>
            </div>

            {/* If bank transfer, display instructions directly */}
            {paymentMethod === 'bank_transfer' && (
              <div className="bg-sky-50/50 border border-sky-100 rounded-2xl p-4 text-xs text-sky-850 space-y-2.5 leading-relaxed shadow-xs">
                <span className="font-bold block text-sm border-b border-sky-100 pb-1.5">Bank Wiring Details:</span>
                <div className="grid grid-cols-2 gap-2">
                  <div>
                    <span className="text-sky-600 text-[10px] uppercase font-bold tracking-wider block">Bank Name:</span>
                    <span className="font-bold">{businessSettings.bankName || 'Access Bank'}</span>
                  </div>
                  <div>
                    <span className="text-sky-600 text-[10px] uppercase font-bold tracking-wider block">Account Number:</span>
                    <span className="font-mono font-bold text-slate-850">{businessSettings.accountNumber || '1480029384'}</span>
                  </div>
                </div>
                <div>
                  <span className="text-sky-600 text-[10px] uppercase font-bold tracking-wider block">Account Name:</span>
                  <span className="font-bold">{businessSettings.accountName || 'Yasmine Booking Studio Ltd'}</span>
                </div>
                <div className="text-[11px] text-sky-700/80 mt-1 border-t border-sky-100/60 pt-2">
                  ⚠️ Please use your email or phone number as transfer narrative for quick lookup.
                </div>
              </div>
            )}

            {/* Form actions */}
            <div className="flex justify-between items-center pt-3 border-t border-slate-100">
              <motion.button
                whileTap={{ scale: 0.94 }}
                type="button"
                onClick={handlePrevStep}
                disabled={isSubmitting}
                className="flex items-center space-x-1.5 px-4.5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-2xl transition disabled:opacity-50 cursor-pointer"
              >
                <ChevronLeft className="w-4 h-4" />
                <span>Details</span>
              </motion.button>

              <motion.button
                whileTap={{ scale: 0.94 }}
                type="submit"
                disabled={isSubmitting}
                className={`flex items-center space-x-1.5 px-5 py-3 text-white text-xs font-bold rounded-2xl transition shadow-sm cursor-pointer ${
                  paymentMethod === 'paystack' ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-[var(--brand-primary)] hover:opacity-95'
                } disabled:opacity-50`}
              >
                {isSubmitting ? (
                  <>
                    <span className="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent" />
                    <span>{isProcessingCard ? 'Verifying Card...' : 'Reserving...'}</span>
                  </>
                ) : (
                  <>
                    <Check className="w-4 h-4 stroke-[2.5]" />
                    <span>{paymentMethod === 'paystack' ? 'Pay Deposit & Book' : 'Complete Booking'}</span>
                  </>
                )}
              </motion.button>
            </div>
          </form>
        )}

        {/* STEP 5: Success Confirmation Screen */}
        {step === 5 && completedBooking && (
          <div id="step-5-success" className="text-center py-6 space-y-5">
            <div className="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-full flex items-center justify-center mx-auto shadow-xs border border-indigo-100">
              <Check className="w-6 h-6 stroke-[3]" />
            </div>

            <div>
              <h2 className="text-lg font-extrabold text-slate-900">Appointment Scheduled!</h2>
              <p className="text-xs text-slate-500 mt-1 leading-relaxed max-w-sm mx-auto">
                Your reservation has been created. Check your inbox for transaction receipts and notifications.
              </p>
            </div>

            <div className="bg-white border border-slate-200/60 rounded-3xl p-5 text-left max-w-sm mx-auto space-y-3.5 text-xs text-slate-700 shadow-xs">
              <div className="flex justify-between border-b border-slate-100 pb-2">
                <span className="font-semibold text-slate-400">Booking ID:</span>
                <span className="font-mono font-bold text-slate-800">{completedBooking.id}</span>
              </div>
              <div className="flex justify-between border-b border-slate-100 pb-2">
                <span className="font-semibold text-slate-400">Unique Code:</span>
                <span className="font-mono font-bold text-indigo-600">{completedBooking.referenceCode}</span>
              </div>
              <div className="flex justify-between border-b border-slate-100 pb-2">
                <span className="font-semibold text-slate-400">Customer Address:</span>
                <span className="font-bold text-slate-800 text-right">{customerAddress}</span>
              </div>
              <div className="flex justify-between border-b border-slate-100 pb-2">
                <span className="font-semibold text-slate-400">Service booked:</span>
                <span className="font-bold text-slate-800">
                  {selectedService.name}
                  {businessSettings.categoryConfig?.enabled && selectedCategoryOptionId && (
                    <span className="block text-[10px] text-slate-400 font-normal">
                      {businessSettings.categoryConfig.label || 'Category'}: {businessSettings.categoryConfig.options.find(opt => opt.id === selectedCategoryOptionId)?.name}
                    </span>
                  )}
                </span>
              </div>
              <div className="flex justify-between border-b border-slate-100 pb-2">
                <span className="font-semibold text-slate-400">Date &amp; Time:</span>
                <span className="font-bold text-slate-800">
                  {new Date(`${selectedDate}T${selectedTime}`).toLocaleDateString([], { weekday: 'short', month: 'short', day: 'numeric' })} at {selectedTime}
                </span>
              </div>
              <div className="flex justify-between border-b border-slate-100 pb-2">
                <span className="font-semibold text-slate-400">Deposit Paid:</span>
                <span className="font-bold text-indigo-600">{formatPrice(completedBooking.depositPaid)}</span>
              </div>
              <div className="flex justify-between">
                <span className="font-semibold text-slate-400">Remaining Balance:</span>
                <span className="font-bold text-slate-800">{formatPrice(completedBooking.balanceDue)}</span>
              </div>
            </div>

            {completedBooking.status === 'pending_payment' && (
              <div className="max-w-sm mx-auto bg-amber-50 border border-amber-200 rounded-2xl p-4 text-left text-[11px] text-amber-850 leading-relaxed shadow-xs space-y-2">
                <div>
                  <span className="font-bold block mb-1">🏦 Action Required:</span>
                  Please wire the deposit amount <span className="font-bold">{formatPrice(completedBooking.depositPaid)}</span> to our bank account. Use booking code <span className="font-mono font-bold">{completedBooking.referenceCode}</span> as reference. The slot is held and will be confirmed once our finance team reconciles the deposit.
                </div>
                <div className="border-t border-amber-200/60 pt-2.5 grid grid-cols-2 gap-2 text-[10px]">
                  <div>
                    <span className="text-amber-700 font-bold block">Bank Name:</span>
                    <span className="font-semibold text-slate-800">{businessSettings.bankName || 'Access Bank'}</span>
                  </div>
                  <div>
                    <span className="text-amber-700 font-bold block">Account Number:</span>
                    <span className="font-mono font-bold text-slate-800">{businessSettings.accountNumber || '1480029384'}</span>
                  </div>
                  <div className="col-span-2">
                    <span className="text-amber-700 font-bold block">Account Name:</span>
                    <span className="font-semibold text-slate-800">{businessSettings.accountName || 'Yasmine Booking Studio Ltd'}</span>
                  </div>
                </div>
              </div>
            )}

            {/* Calendar Reminder Integration */}
            <div className="max-w-sm mx-auto bg-indigo-50/50 border border-indigo-100 rounded-3xl p-4.5 text-center space-y-3 shadow-xs">
              <div className="text-center">
                <span className="text-[10px] font-extrabold uppercase tracking-widest text-indigo-700">📅 Add Reminder to Calendar</span>
                <p className="text-[11px] text-slate-600 mt-1">Don't miss your session! Add this appointment to your preferred calendar.</p>
              </div>
              <div className="grid grid-cols-2 gap-2.5">
                <motion.a
                  whileTap={{ scale: 0.94 }}
                  href={getGoogleCalendarUrl()}
                  target="_blank"
                  rel="noopener noreferrer"
                  onClick={() => onShowToast?.('📅 Opening appointment in Google Calendar!', 'success')}
                  className="flex items-center justify-center space-x-1.5 px-3 py-2.5 bg-white border border-slate-200 hover:border-slate-300 rounded-xl text-[11px] font-bold text-slate-700 transition shadow-xs"
                >
                  <CalendarIcon className="w-3.5 h-3.5 text-indigo-600" />
                  <span>Google Calendar</span>
                </motion.a>
                <motion.button
                  whileTap={{ scale: 0.94 }}
                  type="button"
                  onClick={() => {
                    downloadIcsFile();
                    onShowToast?.('📅 Calendar (.ics) file downloaded!', 'success');
                  }}
                  className="flex items-center justify-center space-x-1.5 px-3 py-2.5 bg-white border border-slate-200 hover:border-slate-300 rounded-xl text-[11px] font-bold text-slate-700 transition shadow-xs cursor-pointer"
                >
                  <Download className="w-3.5 h-3.5 text-emerald-600" />
                  <span>Apple / Outlook (.ics)</span>
                </motion.button>
              </div>
            </div>

            <div className="pt-2">
              <motion.button
                whileTap={{ scale: 0.94 }}
                type="button"
                onClick={resetSimulator}
                className="px-5 py-3 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-2xl transition duration-150 cursor-pointer"
              >
                Submit New Booking
              </motion.button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
