import React from 'react';
import { File, FolderOpen, Download, Copy, Check, Terminal, ExternalLink, HelpCircle, BookOpen } from 'lucide-react';
import JSZip from 'jszip';
import { pluginCodeFiles, pluginPackageInfo } from '../plugin-code';

export default function CodeExporter() {
  const [selectedFilePath, setSelectedFilePath] = React.useState<string>(pluginCodeFiles[0].path);
  const [copied, setCopied] = React.useState<boolean>(false);
  const [zipping, setZipping] = React.useState<boolean>(false);

  const selectedFile = pluginCodeFiles.find(f => f.path === selectedFilePath) || pluginCodeFiles[0];

  const handleCopyCode = () => {
    navigator.clipboard.writeText(selectedFile.content);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  const handleDownloadZip = async (useTimestamped: boolean = false) => {
    setZipping(true);
    const targetFileName = useTimestamped ? pluginPackageInfo.timestampedFile : pluginPackageInfo.primaryFile;
    try {
      // First attempt to download the pre-compiled, verified ZIP from /public
      try {
        const res = await fetch(`/${encodeURIComponent(targetFileName)}`);
        if (res.ok) {
          const blob = await res.blob();
          const url = URL.createObjectURL(blob);
          const link = document.createElement("a");
          link.href = url;
          link.download = targetFileName;
          document.body.appendChild(link);
          link.click();
          document.body.removeChild(link);
          URL.revokeObjectURL(url);
          setZipping(false);
          return;
        }
      } catch {
        // Fallback to client-side JSZip packaging
      }

      const zip = new JSZip();
      const rootFolder = zip.folder("yasmine-artistry-booking");
      
      if (rootFolder) {
        pluginCodeFiles.forEach(file => {
          rootFolder.file(file.path, file.content);
        });
        
        const blob = await zip.generateAsync({ 
          type: "blob",
          compression: "DEFLATE",
          compressionOptions: { level: 6 }
        });
        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.href = url;
        link.download = targetFileName;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
      }
    } catch (err) {
      console.error("Zipping failed:", err);
      alert("ZIP compilation failed. Please copy the file contents manually.");
    } finally {
      setZipping(false);
    }
  };

  return (
    <div id="code-exporter-container" className="grid grid-cols-1 lg:grid-cols-12 gap-6 h-full font-sans text-slate-800 text-xs text-left">
      {/* File Tree Left Section */}
      <div id="file-tree-nav" className="lg:col-span-4 bg-white border border-slate-200/60 rounded-3xl p-5 shadow-xs flex flex-col justify-between">
        <div className="space-y-5">
          <div className="flex items-center space-x-2.5 border-b border-slate-100 pb-3">
            <FolderOpen className="w-5 h-5 text-indigo-600" />
            <div>
              <h3 className="font-bold text-sm text-slate-900">Plugin File Directory</h3>
              <p className="text-[10px] text-slate-400 mt-0.5">Explore the production code directory structure.</p>
            </div>
          </div>

          <div className="space-y-1.5 max-h-96 overflow-y-auto pr-1">
            {pluginCodeFiles.map((file) => {
              const isSelected = selectedFilePath === file.path;
              return (
                <button
                  key={file.path}
                  onClick={() => setSelectedFilePath(file.path)}
                  className={`w-full flex items-start space-x-2.5 p-3 rounded-2xl text-left transition duration-150 ${
                    isSelected 
                      ? 'bg-indigo-50/70 text-indigo-800 font-bold border border-indigo-100/80 shadow-xs' 
                      : 'hover:bg-slate-50 text-slate-600 border border-transparent'
                  }`}
                >
                  <File className={`w-4 h-4 mt-0.5 flex-shrink-0 ${isSelected ? 'text-indigo-600' : 'text-slate-400'}`} />
                  <div className="min-w-0">
                    <span className="font-mono text-xs block truncate">{file.path}</span>
                    <span className="text-[9px] text-slate-400 block mt-0.5 font-normal line-clamp-1">{file.description}</span>
                  </div>
                </button>
              );
            })}
          </div>
        </div>

        {/* Dynamic Download ZIP trigger */}
        <div className="border-t border-slate-100 pt-4 mt-4 space-y-2.5">
          <button
            onClick={() => handleDownloadZip(false)}
            disabled={zipping}
            className="w-full flex items-center justify-center space-x-2 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-2xl transition duration-150 shadow-sm disabled:opacity-50 cursor-pointer text-xs"
          >
            {zipping ? (
              <>
                <span className="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent" />
                <span>Downloading ZIP Package...</span>
              </>
            ) : (
              <>
                <Download className="w-4 h-4" />
                <span>Download {pluginPackageInfo.primaryFile}</span>
              </>
            )}
          </button>

          <button
            onClick={() => handleDownloadZip(true)}
            disabled={zipping}
            className="w-full flex items-center justify-center space-x-2 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-2xl transition duration-150 cursor-pointer text-[11px]"
          >
            <Download className="w-3.5 h-3.5 text-slate-500" />
            <span>Download Timestamped #{pluginPackageInfo.timestamp}</span>
          </button>

          <p className="text-[10px] text-slate-400 leading-normal text-center">
            Standard WordPress archive ready to install without fatal errors on any self-hosted WordPress site.
          </p>
        </div>
      </div>

      {/* Code Viewer Right Section */}
      <div id="code-viewer-panel" className="lg:col-span-8 flex flex-col bg-slate-900 border border-slate-250/10 rounded-3xl overflow-hidden shadow-xs h-[600px]">
        {/* Header Ribbon */}
        <div className="flex items-center justify-between px-5 py-3.5 bg-slate-950 border-b border-slate-800">
          <div className="flex items-center space-x-2">
            <Terminal className="w-4 h-4 text-indigo-400" />
            <span className="font-mono font-bold text-slate-300 text-xs">
              yasmine-artistry-booking/{selectedFile.path}
            </span>
          </div>
          <button
            onClick={handleCopyCode}
            className="flex items-center space-x-1.5 px-3 py-2 bg-slate-800 hover:bg-slate-755 border border-slate-700 rounded-xl text-xs font-bold text-slate-300 transition duration-150 cursor-pointer"
          >
            {copied ? (
              <>
                <Check className="w-3.5 h-3.5 text-indigo-400" />
                <span className="text-indigo-400">Copied!</span>
              </>
            ) : (
              <>
                <Copy className="w-3.5 h-3.5" />
                <span>Copy File Code</span>
              </>
            )}
          </button>
        </div>

        {/* Syntax Scroll container */}
        <div className="flex-1 overflow-auto p-5 bg-slate-950/40">
          <pre className="font-mono text-xs text-slate-300 leading-relaxed whitespace-pre pr-2 select-all">
            {selectedFile.content}
          </pre>
        </div>

        {/* Bottom Documentation banner */}
        <div className="px-5 py-3.5 bg-slate-950 border-t border-slate-800 flex items-center justify-between">
          <div className="flex items-center space-x-2 text-slate-400">
            <BookOpen className="w-4 h-4 text-indigo-400" />
            <span className="text-xs">Includes standard WordPress hooks, sanitation, and row-locking security templates.</span>
          </div>
          <span className="text-[10px] font-mono text-slate-500">PHP 8.1+ &amp; WP 6.0+</span>
        </div>
      </div>

      {/* Custom Setup Documentation Section */}
      <div id="manual-documentation" className="lg:col-span-12 bg-white border border-slate-200/60 rounded-3xl p-6 shadow-xs space-y-5">
        <div className="flex items-center space-x-2 border-b border-slate-100 pb-3">
          <HelpCircle className="w-5 h-5 text-indigo-600" />
          <h3 className="font-bold text-sm text-slate-900">Modular Integrations WordPress Setup Guide</h3>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 leading-relaxed text-xs">
          <div className="space-y-2">
            <h4 className="font-bold text-slate-800 flex items-center space-x-2">
              <span className="w-5 h-5 bg-indigo-50 rounded-full flex items-center justify-center text-[10px] font-bold text-indigo-600">1</span>
              <span>Online Payment (Paystack)</span>
            </h4>
            <p className="text-slate-500 leading-relaxed">
              Sign up at <a href="https://paystack.com" target="_blank" rel="noreferrer" className="text-indigo-600 font-semibold underline inline-flex items-center">Paystack <ExternalLink className="w-3 h-3 ml-0.5" /></a>. Navigate to **Settings &gt; API Keys &amp; Webhooks** and retrieve your **Secret Key** and **Public Key**. Input these keys into the plugin's tab and click **Verify**. The plugin's module will unlock, letting clients checkout securely via card or bank wire directly in the step form.
            </p>
          </div>

          <div className="space-y-2">
            <h4 className="font-bold text-slate-800 flex items-center space-x-2">
              <span className="w-5 h-5 bg-indigo-50 rounded-full flex items-center justify-center text-[10px] font-bold text-indigo-600">2</span>
              <span>Google Calendar Sync</span>
            </h4>
            <p className="text-slate-500 leading-relaxed">
              Go to the **Google Cloud Console**, create a project, and enable the **Google Calendar API**. Under **Credentials**, create an **OAuth 2.0 Client ID** as a *Web Application*. Register the redirect URI provided in your Yasmine Settings. Paste the client credentials into the settings, and trigger standard OAuth consent to lock-in authorization.
            </p>
          </div>

          <div className="space-y-2">
            <h4 className="font-bold text-slate-800 flex items-center space-x-2">
              <span className="w-5 h-5 bg-indigo-50 rounded-full flex items-center justify-center text-[10px] font-bold text-indigo-600">3</span>
              <span>WhatsApp Meta API &amp; SMS</span>
            </h4>
            <p className="text-slate-500 leading-relaxed">
              Set up a Meta Developer Account, enable WhatsApp, and grab your Phone Number ID and System Access Token. For SMS, sign up at Twilio and fetch your SID, Auth Token, and Sender number. Put them in, click **Verify**, and toggle on. Standard transactional triggers (Confirm, Reminder, Cancel) will handle notifications automatically!
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}
