// Mock Database and Initial State for PSO & Bill Reconciliation System

window.INITIAL_DATA = {
  currentUser: {
    id: "usr_01",
    name: "Ramesh Sharma",
    role: "Accountant (PSO Operator)",
    roleCode: "OPERATOR",
    badgeColor: "primary",
    email: "ramesh.sharma@hisabkitap.in",
    avatar: "RS",
    title: "Counter Accountant & PSO Data Operator",
    tagline: "Responsible for PSO counter series creation, Tally import, physical bill verification, discounts/returns, and credit collections.",
    canEditBills: true,
    canImportExcel: true,
    canRecordCorrections: true,
    canRecordCredit: true,
    canApproveSealing: false,
    canConfigurePso: true,
    canEditCutoff: false,
    isReadOnly: false,
    responsibilities: [
      "Configure and create daily PSO Counter Series (Prefixes, Numbering, Specials)",
      "Import Tally Sales Register (Excel / CSV)",
      "Verify physical counter bill bundles (CB 01-10, RB 01-10, Specials)",
      "Record Cash Discount waivers and Goods Returns adjustments",
      "Log salesman / cashier credit recoveries",
      "Generate draft PSO summaries and request reconciliation signoff"
    ],
    restrictions: [
      "Cannot finalize approval or digitally seal daily records (Requires Accounts Officer: Pooja Verma)",
      "Cannot modify System-wide Cutoff Time policy (Requires Super Admin: Suresh Gupta)",
      "Cannot unseal or unlock archived business dates"
    ]
  },
  rolesList: [
    {
      id: "usr_01",
      name: "Ramesh Sharma",
      role: "Accountant (PSO Operator)",
      roleCode: "OPERATOR",
      badgeColor: "primary",
      badgeClass: "bg-primary",
      email: "ramesh.sharma@hisabkitap.in",
      avatar: "RS",
      icon: "bi-person-badge-fill",
      title: "Counter Accountant & PSO Data Operator",
      tagline: "PSO series creation, daily data entry, Tally Excel import, bill checking, and credit collection tracking.",
      canEditBills: true,
      canImportExcel: true,
      canRecordCorrections: true,
      canRecordCredit: true,
      canApproveSealing: false,
      canConfigurePso: true,
      canEditCutoff: false,
      isReadOnly: false,
      responsibilities: [
        "Configure and create daily PSO Counter Series (Prefixes, Numbering, Specials)",
        "Import Tally Sales Register (Excel / CSV)",
        "Verify physical counter bill bundles (CB 01-10, RB 01-10, Specials)",
        "Record Cash Discount waivers and Goods Returns adjustments",
        "Log salesman / cashier credit recoveries",
        "Generate draft PSO summaries and request reconciliation signoff"
      ],
      restrictions: [
        "Cannot finalize approval or digitally seal daily records (Requires Accounts Officer)",
        "Cannot modify System-wide Cutoff Time policy (Requires Super Admin)",
        "Cannot unseal or unlock archived business dates"
      ],
      allowedModules: ["Dashboard", "PSO Series Management", "Tally Excel Import", "Bill Verification", "Payment Classification", "Corrections / Returns", "Credit Collection", "PSO Summary", "Master Reconciliation (Draft View)", "Reports & Exports"]
    },
    {
      id: "usr_02",
      name: "Pooja Verma",
      role: "Accounts Officer (Approver)",
      roleCode: "APPROVER",
      badgeColor: "success",
      badgeClass: "bg-success",
      email: "pooja.verma@hisabkitap.in",
      avatar: "PV",
      icon: "bi-check-circle-fill",
      title: "Senior Accounts Officer & Authorization Signatory",
      tagline: "Final review of reconciliation variance, discount approvals, and Digital Cryptographic Sealing.",
      canEditBills: true,
      canImportExcel: true,
      canRecordCorrections: true,
      canRecordCredit: true,
      canApproveSealing: true,
      canConfigurePso: false,
      canEditCutoff: false,
      isReadOnly: false,
      responsibilities: [
        "Review Master Reconciliation variance and gate checks",
        "Authorize high-value corrections, returns & missing bill resolutions",
        "Execute official PSO Approval & Digital Sealing with Hash Lock",
        "Authorize emergency unsealing with mandatory audit remark",
        "Sign off on formal daily financial reconciliation compliance"
      ],
      restrictions: [
        "Cannot alter Master PSO Counter numbering rules or system architecture"
      ],
      allowedModules: ["Dashboard", "Tally Excel Import", "Bill Verification", "Payment Classification", "Corrections / Returns", "Credit Collection", "PSO Summary", "Master Reconciliation", "Approval & Sealing (Authorized)", "7-Day Retention", "Reports & Exports"]
    },
    {
      id: "usr_03",
      name: "Suresh Gupta (Admin)",
      role: "System Administrator",
      roleCode: "ADMIN",
      badgeColor: "danger",
      badgeClass: "bg-danger",
      email: "admin@hisabkitap.in",
      avatar: "SG",
      icon: "bi-shield-lock-fill",
      title: "ERP & Master Configuration Administrator",
      tagline: "Full master control. PSO counter series setup, cutoff time policy, user access & system locks.",
      canEditBills: true,
      canImportExcel: true,
      canRecordCorrections: true,
      canRecordCredit: true,
      canApproveSealing: true,
      canConfigurePso: true,
      canEditCutoff: true,
      isReadOnly: false,
      responsibilities: [
        "Configure Master PSO counters, series start/end numbers, prefixes & specials",
        "Configure Day Cutoff Time (e.g. 7:00 PM) and Rollover rules",
        "Manage User Access, Roles, Security and Permissions",
        "Emergency lock/unseal operations and data rollbacks",
        "Full unconstrained access to all ERP subsystems"
      ],
      restrictions: [
        "Must follow audit protocol when modifying active PSO master numbering"
      ],
      allowedModules: ["All System Modules (Full Administrative Master Control)"]
    },
    {
      id: "usr_04",
      name: "Vikram Mehta",
      role: "Internal Auditor",
      roleCode: "AUDITOR",
      badgeColor: "warning",
      badgeClass: "bg-warning text-dark",
      email: "auditor@hisabkitap.in",
      avatar: "VM",
      icon: "bi-eye-fill",
      title: "Internal Audit & Statutory Compliance Officer",
      tagline: "Independent oversight. 7-day retention inspection, discrepancy logs, and audit trail compliance.",
      canEditBills: false,
      canImportExcel: false,
      canRecordCorrections: false,
      canRecordCredit: false,
      canApproveSealing: false,
      canConfigurePso: false,
      canEditCutoff: false,
      isReadOnly: true,
      responsibilities: [
        "Conduct independent spot-checks on physical bill verification",
        "Audit 7-day retention log and daily digital seal hashes",
        "Track discrepancy histories, post-cutoff rollover bills and corrections",
        "Export certified reconciliation audit logs to PDF/Excel"
      ],
      restrictions: [
        "STRICTLY READ-ONLY: Cannot modify bill amounts, payment types, or statuses",
        "Cannot import Excel or overwrite financial data",
        "Cannot seal, unseal, or alter PSO configurations"
      ],
      allowedModules: ["All Modules (Read-Only Inspection Mode)", "Audit Trail", "7-Day Retention", "Certified Audit Reports"]
    }
  ],
  businessDate: "2026-08-14",
  cutoffTime: "19:00", // 7:00 PM
  cutoffRuleActive: true,
  isSealed: false,
  sealedInfo: null,

  // Default PSO Configurations
  psoSeries: [
    {
      id: "PSO-1",
      name: "PSO 1 - Main Wholesale Counter",
      prefix: "CB",
      startNo: 1,
      endNo: 10,
      specials: [],
      operator: "Ramesh Sharma",
      active: true,
      description: "Counter Bills CB 01 to CB 10"
    },
    {
      id: "PSO-2",
      name: "PSO 2 - Key Accounts & Special ITC",
      prefix: "CB",
      startNo: 11,
      endNo: 20,
      specials: ["ITC 01", "ITC 03"],
      operator: "Rajesh Kumar",
      active: true,
      description: "Counter Bills CB 11 to CB 20 + ITC 01, ITC 03"
    },
    {
      id: "PSO-3",
      name: "PSO 3 - Retail Counter & Instant Delivery",
      prefix: "RB",
      startNo: 1,
      endNo: 10,
      specials: [],
      operator: "Amit Saxena",
      active: true,
      description: "Retail Bills RB 01 to RB 10"
    }
  ],

  // Detailed Bill List (Tally vs Physical Verification)
  // Reconciled prompt example: Tally = ₹7,00,000, PSO = ₹6,82,500, Diff = ₹17,500
  bills: [
    // PSO 1: CB 01 to CB 10
    { billNo: "CB 01", psoId: "PSO-1", expected: true, tallyFound: true, billDate: "2026-08-14", time: "11:15", customer: "Gupta Traders", amount: 35000, paymentType: "Cash", cd: 0, refund: 0, netAmount: 35000, status: "Matched", remark: "Verified in physical bundle" },
    { billNo: "CB 02", psoId: "PSO-1", expected: true, tallyFound: true, billDate: "2026-08-14", time: "12:00", customer: "Kailash Supermarket", amount: 17500, paymentType: "Cash", cd: 0, refund: 0, netAmount: 17500, status: "Missing", remark: "Pending physical slip from counter 1" },
    { billNo: "CB 03", psoId: "PSO-1", expected: true, tallyFound: true, billDate: "2026-08-14", time: "13:20", customer: "Modern Departmental", amount: 22500, paymentType: "Paytm", cd: 500, refund: 0, netAmount: 22000, status: "Matched", remark: "UPI Ref: 42198730918" },
    { billNo: "CB 04", psoId: "PSO-1", expected: true, tallyFound: true, billDate: "2026-08-14", time: "14:10", customer: "Shri Ram Provision", amount: 18000, paymentType: "Check", cd: 0, refund: 0, netAmount: 18000, status: "Matched", remark: "HDFC Chq #004521" },
    { billNo: "CB 05", psoId: "PSO-1", expected: true, tallyFound: true, billDate: "2026-08-14", time: "14:45", customer: "Balaji Enterprises", amount: 28000, paymentType: "Credit", cd: 0, refund: 0, netAmount: 28000, status: "Matched", remark: "Due date: 21-Aug-2026" },
    { billNo: "CB 06", psoId: "PSO-1", expected: true, tallyFound: true, billDate: "2026-08-14", time: "15:30", customer: "Vikas General Store", amount: 15000, paymentType: "Cash", cd: 0, refund: 0, netAmount: 15000, status: "Matched", remark: "Cashier verified" },
    { billNo: "CB 07", psoId: "PSO-1", expected: true, tallyFound: true, billDate: "2026-08-14", time: "16:00", customer: "Krishna Agencies", amount: 32000, paymentType: "Paytm", cd: 0, refund: 0, netAmount: 32000, status: "Matched", remark: "Paytm QR code slip attached" },
    { billNo: "CB 08", psoId: "PSO-1", expected: true, tallyFound: true, billDate: "2026-08-14", time: "16:40", customer: "Mehta & Sons", amount: 24000, paymentType: "Credit", cd: 1000, refund: 0, netAmount: 23000, status: "Matched", remark: "Salesman: Rajesh Kumar" },
    { billNo: "CB 09", psoId: "PSO-1", expected: true, tallyFound: true, billDate: "2026-08-14", time: "17:15", customer: "Ambika Mart", amount: 16000, paymentType: "Check", cd: 0, refund: 0, netAmount: 16000, status: "Matched", remark: "SBI Chq #883921" },
    { billNo: "CB 10", psoId: "PSO-1", expected: true, tallyFound: true, billDate: "2026-08-14", time: "18:30", customer: "Sharma Kirana", amount: 20000, paymentType: "Cash", cd: 0, refund: 0, netAmount: 20000, status: "Matched", remark: "Closing counter bill" },

    // PSO 2: CB 11 to CB 20 + ITC 01, ITC 03
    { billNo: "CB 11", psoId: "PSO-2", expected: true, tallyFound: true, billDate: "2026-08-14", time: "11:40", customer: "Ahuja Wholesalers", amount: 30000, paymentType: "Cash", cd: 0, refund: 0, netAmount: 30000, status: "Matched", remark: "Full cash received" },
    { billNo: "CB 12", psoId: "PSO-2", expected: true, tallyFound: true, billDate: "2026-08-14", time: "12:15", customer: "Agarwal Sweets & Provisions", amount: 25000, paymentType: "Paytm", cd: 0, refund: 0, netAmount: 25000, status: "Matched", remark: "Paytm Soundbox confirmed" },
    { billNo: "CB 13", psoId: "PSO-2", expected: true, tallyFound: true, billDate: "2026-08-14", time: "13:00", customer: "New Delhi Mart", amount: 45000, paymentType: "Check", cd: 0, refund: 0, netAmount: 45000, status: "Matched", remark: "ICICI Chq #112093" },
    { billNo: "CB 14", psoId: "PSO-2", expected: true, tallyFound: true, billDate: "2026-08-14", time: "14:10", customer: "Om Sai Enterprises", amount: 22000, paymentType: "Credit", cd: 0, refund: 0, netAmount: 22000, status: "Matched", remark: "Salesman: Amit Sharma" },
    { billNo: "CB 15", psoId: "PSO-2", expected: true, tallyFound: true, billDate: "2026-08-14", time: "15:20", customer: "Garg Confectioners", amount: 18000, paymentType: "Cash", cd: 0, refund: 0, netAmount: 18000, status: "Matched", remark: "Counter 2 verify" },
    { billNo: "CB 16", psoId: "PSO-2", expected: true, tallyFound: true, billDate: "2026-08-14", time: "16:05", customer: "Ratan Stores", amount: 15000, paymentType: "Cancelled", cd: 0, refund: 0, netAmount: 0, status: "Cancelled", remark: "Customer cancelled before dispatch" },
    { billNo: "CB 17", psoId: "PSO-2", expected: true, tallyFound: true, billDate: "2026-08-14", time: "16:50", customer: "City Cash & Carry", amount: 35000, paymentType: "Check", cd: 0, refund: 0, netAmount: 35000, status: "Matched", remark: "Axis Bank Chq #40921" },
    { billNo: "CB 18", psoId: "PSO-2", expected: true, tallyFound: true, billDate: "2026-08-14", time: "17:35", customer: "Jindal Retailers", amount: 20000, paymentType: "Paytm", cd: 0, refund: 0, netAmount: 20000, status: "Matched", remark: "UPI verified" },
    { billNo: "CB 19", psoId: "PSO-2", expected: true, tallyFound: true, billDate: "2026-08-14", time: "18:10", customer: "Universal Traders", amount: 27000, paymentType: "Cash", cd: 1000, refund: 1000, netAmount: 25000, status: "Matched", remark: "Return 1 item damaged" },
    { billNo: "CB 20", psoId: "PSO-2", expected: true, tallyFound: true, billDate: "2026-08-14", time: "18:45", customer: "Sunil Brother Co", amount: 23000, paymentType: "Credit", cd: 0, refund: 0, netAmount: 23000, status: "Matched", remark: "Salesman: Rajesh Kumar" },
    // Specials for PSO 2
    { billNo: "ITC 01", psoId: "PSO-2", expected: true, tallyFound: true, billDate: "2026-08-14", time: "15:00", customer: "ITC Direct Depo Wholesale", amount: 25000, paymentType: "Check", cd: 0, refund: 0, netAmount: 25000, status: "Matched", remark: "Direct Company Stock Transfer Bill" },
    { billNo: "ITC 03", psoId: "PSO-2", expected: true, tallyFound: true, billDate: "2026-08-14", time: "17:40", customer: "ITC Food Hub Distributor", amount: 20000, paymentType: "Paytm", cd: 0, refund: 0, netAmount: 20000, status: "Matched", remark: "Special Corporate Promo Bill" },

    // PSO 3: RB 01 to RB 10
    { billNo: "RB 01", psoId: "PSO-3", expected: true, tallyFound: true, billDate: "2026-08-14", time: "10:30", customer: "Direct Walk-in 101", amount: 12500, paymentType: "Cash", cd: 0, refund: 0, netAmount: 12500, status: "Matched", remark: "Express POS slip" },
    { billNo: "RB 02", psoId: "PSO-3", expected: true, tallyFound: true, billDate: "2026-08-14", time: "11:20", customer: "Direct Walk-in 102", amount: 18000, paymentType: "Paytm", cd: 0, refund: 0, netAmount: 18000, status: "Matched", remark: "QR scan payment" },
    { billNo: "RB 03", psoId: "PSO-3", expected: true, tallyFound: true, billDate: "2026-08-14", time: "12:40", customer: "Direct Walk-in 103", amount: 14000, paymentType: "Cash", cd: 0, refund: 0, netAmount: 14000, status: "Matched", remark: "Exact cash collected" },
    { billNo: "RB 04", psoId: "PSO-3", expected: true, tallyFound: true, billDate: "2026-08-14", time: "13:50", customer: "Direct Walk-in 104", amount: 22000, paymentType: "Check", cd: 0, refund: 0, netAmount: 22000, status: "Matched", remark: "Bank of Baroda Chq #9012" },
    { billNo: "RB 05", psoId: "PSO-3", expected: true, tallyFound: true, billDate: "2026-08-14", time: "14:30", customer: "Direct Walk-in 105", amount: 16000, paymentType: "Paytm", cd: 0, refund: 0, netAmount: 16000, status: "Matched", remark: "UPI transfer verified" },
    { billNo: "RB 06", psoId: "PSO-3", expected: true, tallyFound: true, billDate: "2026-08-14", time: "15:45", customer: "Direct Walk-in 106", amount: 25000, paymentType: "Credit", cd: 0, refund: 0, netAmount: 25000, status: "Matched", remark: "Local institutional credit (Govt School)" },
    { billNo: "RB 07", psoId: "PSO-3", expected: true, tallyFound: true, billDate: "2026-08-14", time: "16:20", customer: "Direct Walk-in 107", amount: 11000, paymentType: "Cash", cd: 0, refund: 0, netAmount: 11000, status: "Matched", remark: "Retail verified" },
    { billNo: "RB 08", psoId: "PSO-3", expected: true, tallyFound: true, billDate: "2026-08-14", time: "17:10", customer: "Direct Walk-in 108", amount: 19000, paymentType: "Paytm", cd: 0, refund: 0, netAmount: 19000, status: "Matched", remark: "GooglePay confirmed" },
    { billNo: "RB 09", psoId: "PSO-3", expected: true, tallyFound: true, billDate: "2026-08-14", time: "18:00", customer: "Direct Walk-in 109", amount: 15000, paymentType: "Cash", cd: 0, refund: 0, netAmount: 15000, status: "Matched", remark: "Counter cash intact" },
    { billNo: "RB 10", psoId: "PSO-3", expected: true, tallyFound: true, billDate: "2026-08-14", time: "18:50", customer: "Direct Walk-in 110", amount: 25000, paymentType: "Check", cd: 0, refund: 0, netAmount: 25000, status: "Matched", remark: "PNB Chq #332014" }
  ],

  // Extra Post-cutoff bills for demo
  postCutoffBills: [
    { billNo: "CB 21", psoId: "PSO-2", expected: false, tallyFound: true, billDate: "2026-08-14", time: "19:25", customer: "Night Owl Grocers", amount: 14500, paymentType: "Cash", status: "Next Day PSO", remark: "Entered after 7:00 PM cutoff - Assigned to 15-Aug PSO" },
    { billNo: "RB 11", psoId: "PSO-3", expected: false, tallyFound: true, billDate: "2026-08-14", time: "19:40", customer: "Late Walk-in 111", amount: 8200, paymentType: "Paytm", status: "Next Day PSO", remark: "Entered after 7:00 PM cutoff - Assigned to 15-Aug PSO" }
  ],

  // Corrections Registry
  corrections: [
    {
      id: "CORR-01",
      billNo: "CB 03",
      originalAmount: 22500,
      correctionType: "Cash Discount",
      cdAmount: 500,
      goodsReturnAmount: 0,
      refundAmount: 0,
      netAdjustment: -500,
      reason: "Volume rebate discount authorized on billing",
      approvedBy: "Pooja Verma",
      timestamp: "2026-08-14 14:05"
    },
    {
      id: "CORR-02",
      billNo: "CB 08",
      originalAmount: 24000,
      correctionType: "Cash Discount",
      cdAmount: 1000,
      goodsReturnAmount: 0,
      refundAmount: 0,
      netAdjustment: -1000,
      reason: "Early payment incentive 4% agreed",
      approvedBy: "Pooja Verma",
      timestamp: "2026-08-14 17:00"
    },
    {
      id: "CORR-03",
      billNo: "CB 19",
      originalAmount: 27000,
      correctionType: "Goods Return",
      cdAmount: 1000,
      goodsReturnAmount: 1000,
      refundAmount: 1000,
      netAdjustment: -2000,
      reason: "1 carton crushed during transit + spot discount",
      approvedBy: "Suresh Gupta",
      timestamp: "2026-08-14 18:30"
    }
  ],

  // Credit Collection Register
  creditCollections: [
    {
      billNo: "CB 05",
      customer: "Balaji Enterprises",
      salesman: "Rajesh Kumar",
      billDate: "2026-08-14",
      billAmount: 28000,
      paidAmount: 0,
      outstanding: 28000,
      collectionStatus: "Pending",
      dueDate: "2026-08-21",
      remark: "Follow-up scheduled on Monday"
    },
    {
      billNo: "CB 08",
      customer: "Mehta & Sons",
      salesman: "Rajesh Kumar",
      billDate: "2026-08-14",
      billAmount: 23000,
      paidAmount: 10000,
      outstanding: 13000,
      collectionStatus: "Partially Collected",
      dueDate: "2026-08-19",
      remark: "₹10,000 received via GPay, balance next week"
    },
    {
      billNo: "CB 14",
      customer: "Om Sai Enterprises",
      salesman: "Amit Sharma",
      billDate: "2026-08-14",
      billAmount: 22000,
      paidAmount: 0,
      outstanding: 22000,
      collectionStatus: "Pending",
      dueDate: "2026-08-28",
      remark: "14 days credit cycle approved"
    },
    {
      billNo: "CB 20",
      customer: "Sunil Brother Co",
      salesman: "Rajesh Kumar",
      billDate: "2026-08-14",
      billAmount: 23000,
      paidAmount: 23000,
      outstanding: 0,
      collectionStatus: "Collected",
      dueDate: "2026-08-16",
      remark: "Full payment received by Cheque #89112"
    },
    {
      billNo: "RB 06",
      customer: "Direct Walk-in 106 (Govt School)",
      salesman: "Amit Sharma",
      billDate: "2026-08-14",
      billAmount: 25000,
      paidAmount: 0,
      outstanding: 25000,
      collectionStatus: "Pending",
      dueDate: "2026-08-30",
      remark: "Government treasury bill voucher submitted"
    }
  ],

  // 7-Day Retention Register
  retentionPsoList: [
    {
      psoCode: "PSO 2",
      businessDate: "2026-08-14",
      createdDate: "14-Aug-2026",
      daysRemaining: 7,
      totalAmount: 280000,
      status: "Pending Approval",
      badgeClass: "bg-warning text-dark"
    },
    {
      psoCode: "PSO 1",
      businessDate: "2026-08-13",
      createdDate: "13-Aug-2026",
      daysRemaining: 6,
      totalAmount: 215000,
      status: "Sealed & Approved",
      badgeClass: "bg-success"
    },
    {
      psoCode: "PSO 3",
      businessDate: "2026-08-12",
      createdDate: "12-Aug-2026",
      daysRemaining: 5,
      totalAmount: 168000,
      status: "Sealed & Approved",
      badgeClass: "bg-success"
    },
    {
      psoCode: "PSO 2",
      businessDate: "2026-08-11",
      createdDate: "11-Aug-2026",
      daysRemaining: 4,
      totalAmount: 242000,
      status: "Audit Cleared",
      badgeClass: "bg-info text-white"
    },
    {
      psoCode: "PSO 1",
      businessDate: "2026-08-10",
      createdDate: "10-Aug-2026",
      daysRemaining: 3,
      totalAmount: 195000,
      status: "Sealed & Approved",
      badgeClass: "bg-success"
    },
    {
      psoCode: "PSO 3",
      businessDate: "2026-08-09",
      createdDate: "09-Aug-2026",
      daysRemaining: 2,
      totalAmount: 182000,
      status: "Sealed & Approved",
      badgeClass: "bg-success"
    },
    {
      psoCode: "PSO 1",
      businessDate: "2026-08-08",
      createdDate: "08-Aug-2026",
      daysRemaining: 1,
      totalAmount: 204000,
      status: "Auto-Archived",
      badgeClass: "bg-secondary"
    }
  ],

  // Recent Import Log
  recentImports: [
    { filename: "Tally_DayBook_14Aug2026.xlsx", time: "14-Aug-2026 18:45", records: 32, amount: 700000, status: "Imported & Scanned", operator: "Ramesh Sharma" },
    { filename: "Tally_DayBook_13Aug2026.xlsx", time: "13-Aug-2026 19:10", records: 28, amount: 645000, status: "Sealed & Reconciled", operator: "Ramesh Sharma" },
    { filename: "Tally_DayBook_12Aug2026.xlsx", time: "12-Aug-2026 19:05", records: 30, amount: 692000, status: "Sealed & Reconciled", operator: "Ramesh Sharma" }
  ],

  // System Audit Trail
  auditTrail: [
    { timestamp: "2026-08-14 18:45:10", user: "Ramesh Sharma", action: "EXCEL_IMPORT", details: "Imported Tally_DayBook_14Aug2026.xlsx with 32 records total ₹7,00,000" },
    { timestamp: "2026-08-14 18:48:22", user: "Ramesh Sharma", action: "VERIFY_SEQUENCE", details: "Sequence verification ran. Identified Missing bill CB 02 (₹17,500)" },
    { timestamp: "2026-08-14 18:50:00", user: "Pooja Verma", action: "RECON_CHECK", details: "Reconciliation evaluated: Difference of ₹17,500 detected. Approval blocked." }
  ]
};
