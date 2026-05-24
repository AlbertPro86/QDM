// ds-icons.jsx — small set of Lucide-style outline icons (1.5px stroke)
// All accept { size, strokeWidth, ...rest } and inherit currentColor.

const Icon = ({ size = 16, strokeWidth = 1.5, children, ...rest }) => (
  <svg
    width={size} height={size} viewBox="0 0 24 24"
    fill="none" stroke="currentColor" strokeWidth={strokeWidth}
    strokeLinecap="round" strokeLinejoin="round"
    aria-hidden="true" focusable="false" {...rest}
  >{children}</svg>
);

const IconHome      = (p)=> <Icon {...p}><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20h14V9.5"/><path d="M10 20v-6h4v6"/></Icon>;
const IconLeads     = (p)=> <Icon {...p}><circle cx="9" cy="8" r="3.2"/><path d="M2.8 19c.6-3.2 3.1-5 6.2-5s5.6 1.8 6.2 5"/><circle cx="17" cy="7" r="2.4"/><path d="M21.3 17.5c-.5-2.3-2-3.7-4.3-3.7"/></Icon>;
const IconUsers     = (p)=> <Icon {...p}><circle cx="9" cy="8" r="3.2"/><path d="M2.5 19.5c.7-3.5 3.4-5.3 6.5-5.3s5.8 1.8 6.5 5.3"/><path d="M17 4.5c1.8 0 3.2 1.4 3.2 3.2S18.8 11 17 11"/><path d="M19.5 14.5c1.4.5 2.4 1.7 2.8 3.5"/></Icon>;
const IconCalc      = (p)=> <Icon {...p}><rect x="4" y="3" width="16" height="18" rx="1.5"/><rect x="7" y="6" width="10" height="3.5"/><circle cx="8" cy="13" r=".6" fill="currentColor"/><circle cx="12" cy="13" r=".6" fill="currentColor"/><circle cx="16" cy="13" r=".6" fill="currentColor"/><circle cx="8" cy="17" r=".6" fill="currentColor"/><circle cx="12" cy="17" r=".6" fill="currentColor"/><circle cx="16" cy="17" r=".6" fill="currentColor"/></Icon>;
const IconDoc       = (p)=> <Icon {...p}><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path d="M9 13h6M9 17h4"/></Icon>;
const IconMail      = (p)=> <Icon {...p}><rect x="3" y="5" width="18" height="14" rx="1.5"/><path d="m3.5 6 8.5 7L20.5 6"/></Icon>;
const IconChat      = (p)=> <Icon {...p}><path d="M21 12c0 4.4-4 8-9 8-1.3 0-2.6-.2-3.7-.7L3 21l1.4-4.4C3.5 15.2 3 13.6 3 12c0-4.4 4-8 9-8s9 3.6 9 8z"/></Icon>;
const IconCheck     = (p)=> <Icon {...p}><path d="m5 12 4 4L19 7"/></Icon>;
const IconCheckSq   = (p)=> <Icon {...p}><rect x="3" y="3" width="18" height="18" rx="2"/><path d="m8 12 3 3 5-6"/></Icon>;
const IconCoin      = (p)=> <Icon {...p}><circle cx="12" cy="12" r="9"/><path d="M12 7v10M9.5 9.2c.6-.7 1.5-1 2.5-1 1.5 0 2.5.8 2.5 2 0 1-.6 1.6-2 2-1.5.4-2 1-2 2 0 1.2 1 2 2.5 2 1 0 1.9-.3 2.5-1"/></Icon>;
const IconTruck     = (p)=> <Icon {...p}><rect x="2" y="6" width="13" height="10" rx="1.5"/><path d="M15 9h4l3 4v3h-7"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/></Icon>;
const IconBolt      = (p)=> <Icon {...p}><path d="M13 2 4 14h7l-1 8 9-12h-7z"/></Icon>;
const IconLayers    = (p)=> <Icon {...p}><path d="M12 2 3 7l9 5 9-5z"/><path d="m3 12 9 5 9-5"/><path d="m3 17 9 5 9-5"/></Icon>;
const IconCog       = (p)=> <Icon {...p}><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 0 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 0 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3h0a1.7 1.7 0 0 0 1-1.5V3a2 2 0 0 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8v0a1.7 1.7 0 0 0 1.5 1H21a2 2 0 0 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/></Icon>;
const IconBell      = (p)=> <Icon {...p}><path d="M6 8a6 6 0 1 1 12 0c0 7 3 8 3 9H3c0-1 3-2 3-9z"/><path d="M10 21h4"/></Icon>;
const IconChevR     = (p)=> <Icon {...p}><path d="m9 6 6 6-6 6"/></Icon>;
const IconChevL     = (p)=> <Icon {...p}><path d="m15 6-6 6 6 6"/></Icon>;
const IconChevD     = (p)=> <Icon {...p}><path d="m6 9 6 6 6-6"/></Icon>;
const IconChevDouble= (p)=> <Icon {...p}><path d="m13 6-5 6 5 6"/><path d="m18 6-5 6 5 6"/></Icon>;
const IconPlus      = (p)=> <Icon {...p}><path d="M12 5v14M5 12h14"/></Icon>;
const IconSearch    = (p)=> <Icon {...p}><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></Icon>;
const IconFilter    = (p)=> <Icon {...p}><path d="M4 5h16l-6 8v6l-4-2v-4z"/></Icon>;
const IconCopy      = (p)=> <Icon {...p}><rect x="8" y="8" width="12" height="12" rx="1.5"/><path d="M16 8V5a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h3"/></Icon>;
const IconEdit      = (p)=> <Icon {...p}><path d="M4 20h4l10-10-4-4L4 16z"/><path d="m14 6 4 4"/></Icon>;
const IconTrash     = (p)=> <Icon {...p}><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/><path d="M10 11v6M14 11v6"/></Icon>;
const IconEye       = (p)=> <Icon {...p}><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></Icon>;
const IconX         = (p)=> <Icon {...p}><path d="M6 6l12 12M18 6 6 18"/></Icon>;
const IconWhatsApp  = (p)=> <Icon {...p}><path d="M20.5 12a8.5 8.5 0 0 1-12.7 7.4L3 21l1.7-4.7A8.5 8.5 0 1 1 20.5 12z"/><path d="M8.5 9c.2 2.5 2 4.3 4.5 4.5l1-1 2 1c-.5 1.5-2 2-3.5 1.5-2-.5-3.5-2-4-4-.5-1.5 0-3 1.5-3.5l1 2z"/></Icon>;
const IconBox       = (p)=> <Icon {...p}><path d="M3 7 12 3l9 4v10l-9 4-9-4z"/><path d="m3 7 9 4 9-4M12 11v10"/></Icon>;
const IconExport    = (p)=> <Icon {...p}><path d="M12 3v12M7 8l5-5 5 5"/><path d="M5 15v4a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-4"/></Icon>;
const IconSparkle   = (p)=> <Icon {...p}><path d="m12 3 2 6 6 2-6 2-2 6-2-6-6-2 6-2z"/></Icon>;
const IconUpDown    = (p)=> <Icon {...p}><path d="m8 9 4-4 4 4M8 15l4 4 4-4"/></Icon>;
const IconLink      = (p)=> <Icon {...p}><path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 1 0-5.7-5.7L11.5 7"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 1 0 5.7 5.7L12.5 17"/></Icon>;

Object.assign(window, {
  IconHome, IconLeads, IconUsers, IconCalc, IconDoc, IconMail, IconChat, IconCheck, IconCheckSq,
  IconCoin, IconTruck, IconBolt, IconLayers, IconCog, IconBell, IconChevR, IconChevL, IconChevD,
  IconChevDouble, IconPlus, IconSearch, IconFilter, IconCopy, IconEdit, IconTrash, IconEye, IconX,
  IconWhatsApp, IconBox, IconExport, IconSparkle, IconUpDown, IconLink,
});
