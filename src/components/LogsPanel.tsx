import React from 'react';
import { 
  Mail, MessageSquare, Phone, Calendar, CreditCard, Clock, 
  Trash2, ShieldCheck, AlertCircle, RefreshCw 
} from 'lucide-react';
import { NotificationLog } from '../types';

interface LogsPanelProps {
  logs: NotificationLog[];
  onClearLogs: () => void;
}

export default function LogsPanel({ logs, onClearLogs }: LogsPanelProps) {
  const [filter, setFilter] = React.useState<string>('all');

  const filteredLogs = logs.filter(log => filter === 'all' || log.type === filter);

  const getIcon = (type: NotificationLog['type']) => {
    switch (type) {
      case 'email':
        return <Mail className="w-4 h-4 text-emerald-500" />;
      case 'whatsapp':
        return <MessageSquare className="w-4 h-4 text-green-500" />;
      case 'sms':
        return <Phone className="w-4 h-4 text-blue-500" />;
      case 'gcal':
        return <Calendar className="w-4 h-4 text-red-500" />;
      case 'paystack':
        return <CreditCard className="w-4 h-4 text-cyan-500" />;
      case 'cron':
        return <Clock className="w-4 h-4 text-amber-500" />;
    }
  };

  const getBadgeColor = (type: NotificationLog['type']) => {
    switch (type) {
      case 'email': return 'bg-emerald-50 text-emerald-700 border-emerald-200';
      case 'whatsapp': return 'bg-green-50 text-green-700 border-green-200';
      case 'sms': return 'bg-blue-50 text-blue-700 border-blue-200';
      case 'gcal': return 'bg-red-50 text-red-700 border-red-200';
      case 'paystack': return 'bg-cyan-50 text-cyan-700 border-cyan-200';
      case 'cron': return 'bg-amber-50 text-amber-700 border-amber-200';
    }
  };

  return (
    <div id="logs-panel-container" className="flex flex-col h-full bg-white border border-slate-200/60 rounded-3xl overflow-hidden font-sans shadow-xs">
      {/* Header */}
      <div id="logs-panel-header" className="flex items-center justify-between px-5 py-4 bg-slate-50/50 border-b border-slate-150">
        <div className="flex items-center space-x-2">
          <div className="w-2.5 h-2.5 rounded-full bg-indigo-500 animate-pulse" />
          <span className="text-xs font-bold text-slate-800 tracking-wide uppercase">
            System Dispatch Logs &amp; Integrations
          </span>
        </div>
        {logs.length > 0 && (
          <button 
            onClick={onClearLogs}
            className="flex items-center space-x-1.5 px-3 py-1.5 bg-white hover:bg-slate-50 border border-slate-200 hover:border-slate-300 shadow-xs rounded-xl text-xs font-semibold text-slate-700 transition duration-150"
          >
            <Trash2 className="w-3.5 h-3.5 text-slate-500" />
            <span>Clear Logs</span>
          </button>
        )}
      </div>

      {/* Filter Bar */}
      <div id="logs-panel-filter-bar" className="flex items-center space-x-1.5 px-4 py-2.5 bg-slate-50/10 border-b border-slate-100 overflow-x-auto scrollbar-none">
        {['all', 'email', 'whatsapp', 'sms', 'gcal', 'paystack', 'cron'].map((type) => (
          <button
            key={type}
            onClick={() => setFilter(type)}
            className={`px-3 py-1.5 rounded-xl text-xs font-semibold transition duration-150 whitespace-nowrap capitalize ${
              filter === type 
                ? 'bg-indigo-600 text-white shadow-xs border border-indigo-600' 
                : 'text-slate-500 hover:bg-slate-100/60 hover:text-slate-800 border border-transparent'
            }`}
          >
            {type === 'gcal' ? 'Google Calendar' : type === 'cron' ? 'WP Cron' : type}
          </button>
        ))}
      </div>

      {/* Logs View */}
      <div id="logs-list-view" className="flex-1 overflow-y-auto p-5 space-y-4 min-h-[250px] bg-slate-50/20">
        {filteredLogs.length === 0 ? (
          <div className="flex flex-col items-center justify-center h-full py-16 text-slate-500 text-center space-y-3">
            <Clock className="w-10 h-10 text-slate-300" />
            <p className="text-sm font-bold text-slate-700">No active logs matching filter</p>
            <p className="text-xs max-w-xs text-slate-400 leading-relaxed">
              Trigger background activities by submitting bookings on the front-end form or updating appointment statuses in the WP Admin.
            </p>
          </div>
        ) : (
          filteredLogs.map((log) => (
            <div 
              key={log.id} 
              className="group flex flex-col border border-slate-200/60 rounded-2xl bg-white overflow-hidden hover:border-indigo-150 hover:shadow-xs transition duration-150"
            >
              <div className="flex items-center justify-between px-4 py-3 bg-slate-50/40 border-b border-slate-100">
                <div className="flex items-center space-x-2">
                  <span className={`flex items-center space-x-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide border ${getBadgeColor(log.type)}`}>
                    {getIcon(log.type)}
                    <span className="ml-1 text-[9px]">{log.type === 'gcal' ? 'G-Cal' : log.type}</span>
                  </span>
                  <span className="font-bold text-xs text-slate-800">
                    {log.title}
                  </span>
                </div>
                <div className="flex items-center space-x-2">
                  <span className="text-[10px] text-slate-400 font-mono">
                    {new Date(log.timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' })}
                  </span>
                  <span className={`w-2 h-2 rounded-full ${log.status === 'success' ? 'bg-emerald-500' : 'bg-rose-500'}`} />
                </div>
              </div>

              <div className="p-4 bg-white">
                {log.recipient && (
                  <div className="flex items-center space-x-1 text-[11px] text-slate-500 mb-2 font-mono">
                    <span className="text-slate-400">To:</span>
                    <span className="font-semibold text-slate-600">{log.recipient}</span>
                  </div>
                )}
                
                {log.type === 'email' ? (
                  <div className="border border-slate-150 rounded-xl bg-slate-50/55 p-3 font-sans">
                    <div 
                      className="text-xs text-slate-700 leading-relaxed max-h-48 overflow-y-auto pr-1"
                      dangerouslySetInnerHTML={{ __html: log.content }}
                    />
                  </div>
                ) : (
                  <pre className="font-mono text-xs text-slate-700 bg-slate-50/55 p-3 rounded-xl border border-slate-150 overflow-x-auto whitespace-pre-wrap leading-relaxed">
                    {log.content}
                  </pre>
                )}
              </div>
            </div>
          ))
        )}
      </div>
    </div>
  );
}
