<?php

return [
    'studio_name' => env('CRM_STUDIO_NAME', 'Grovera Studio'),
    'currency' => env('CRM_CURRENCY', 'INR'),
    'currency_symbol' => env('CRM_CURRENCY_SYMBOL', '₹'),
    'date_format' => env('CRM_DATE_FORMAT', 'd M Y'),
    'timezone' => env('CRM_TIMEZONE', 'Asia/Kolkata'),

    'influencer_types' => [
        'premium' => 'Premium',
        'medium' => 'Medium',
        'low' => 'Low',
    ],

    'campaign_types' => [
        'influencer_marketing' => 'Influencer Marketing',
        'social_media_marketing' => 'Social Media Marketing',
        'both' => 'Both',
    ],

    'platforms' => [
        'instagram' => 'Instagram',
        'facebook' => 'Facebook',
        'linkedin' => 'LinkedIn',
        'youtube' => 'YouTube',
        'twitter' => 'X / Twitter',
        'other' => 'Other',
    ],

    'client_statuses' => [
        'lead' => 'Lead',
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],

    'client_activity_types' => [
        'called_client' => 'Called Client',
        'whatsapp' => 'WhatsApp',
        'email' => 'Email',
        'meeting' => 'Meeting',
        'proposal_sent' => 'Proposal Sent',
        'follow_up' => 'Follow-up',
        'campaign_discussion' => 'Campaign Discussion',
        'payment_discussion' => 'Payment Discussion',
        'other' => 'Other',
    ],

    'campaign_statuses' => [
        'draft' => 'Draft',
        'active' => 'Active',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    'collaboration_statuses' => [
        'new_lead' => 'New Lead',
        'contacted' => 'Contacted',
        'negotiating' => 'Negotiating',
        'price_confirmed' => 'Price Confirmed',
        'waiting_for_product' => 'Waiting for Product',
        'product_received' => 'Product Received',
        'script_shared' => 'Script Shared',
        'content_in_progress' => 'Content in Progress',
        'content_received' => 'Content Received',
        'sent_for_approval' => 'Sent for Approval',
        'approved' => 'Approved',
        'scheduled' => 'Scheduled',
        'posted' => 'Posted',
        'payment_pending' => 'Payment Pending',
        'payment_completed' => 'Payment Completed',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    'deliverable_types' => [
        'instagram_reel' => 'Instagram Reel',
        'instagram_story' => 'Instagram Story',
        'instagram_post' => 'Instagram Post',
        'static_post' => 'Static Post',
        'collaboration_post' => 'Collaboration Post',
        'facebook_post' => 'Facebook Post',
        'linkedin_post' => 'LinkedIn Post',
        'youtube_short' => 'YouTube Short',
        'youtube_video' => 'YouTube Video',
        'product_review' => 'Product Review',
        'social_media_management' => 'Social Media Management',
        'content_calendar' => 'Content Calendar',
        'graphic_design' => 'Graphic Design',
        'custom' => 'Custom',
    ],

    'deliverable_statuses' => [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'submitted' => 'Submitted',
        'approved' => 'Approved',
        'posted' => 'Posted',
    ],

    'content_approval_statuses' => [
        'not_submitted' => 'Not Submitted',
        'submitted' => 'Submitted',
        'revision_requested' => 'Revision Requested',
        'approved' => 'Approved',
    ],

    'activity_types' => [
        'called' => 'Called',
        'whatsapp_sent' => 'WhatsApp Sent',
        'email_sent' => 'Email Sent',
        'instagram_dm' => 'Instagram DM',
        'follow_up' => 'Follow-up',
        'price_negotiation' => 'Price Negotiation',
        'script_shared' => 'Script Shared',
        'product_dispatched' => 'Product Dispatched',
        'product_received' => 'Product Received',
        'content_received' => 'Content Received',
        'approval_requested' => 'Approval Requested',
        'payment_done' => 'Payment Done',
        'other' => 'Other',
    ],

    'follow_up_statuses' => [
        'pending' => 'Pending',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    'payment_methods' => [
        'bank_transfer' => 'Bank Transfer',
        'upi' => 'UPI',
        'cash' => 'Cash',
        'cheque' => 'Cheque',
        'other' => 'Other',
    ],

    'payment_statuses' => [
        'not_paid' => 'Not Paid',
        'partially_paid' => 'Partially Paid',
        'paid' => 'Paid',
        'payment_pending' => 'Payment Pending',
    ],

    'dashboard_ranges' => [
        'this_month' => 'This Month',
        'last_month' => 'Last Month',
        'this_quarter' => 'This Quarter',
        'this_year' => 'This Year',
        'custom' => 'Custom Range',
    ],

    'requirement_types' => [
        'influencer_marketing' => 'Influencer Marketing',
        'social_media_marketing' => 'Social Media Marketing',
        'both' => 'Both',
    ],

    'requirement_statuses' => [
        'submitted' => 'Submitted',
        'under_review' => 'Under Review',
        'need_more_information' => 'Need More Information',
        'shortlisting' => 'Shortlisting',
        'proposal_shared' => 'Proposal Shared',
        'client_reviewing' => 'Client Reviewing',
        'approved' => 'Approved',
        'converted_to_campaign' => 'Converted to Campaign',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
        'closed' => 'Closed',
    ],

    'shortlist_statuses' => [
        'draft' => 'Draft',
        'shared' => 'Shared',
        'viewed' => 'Viewed',
        'responded' => 'Responded',
        'withdrawn' => 'Withdrawn',
        'expired' => 'Expired',
    ],

    'shortlist_item_statuses' => [
        'pending' => 'Pending',
        'interested' => 'Interested',
        'rejected' => 'Rejected',
        'need_more_details' => 'Need More Details',
        'selected' => 'Selected',
    ],

    'preferred_categories' => [
        'lifestyle' => 'Lifestyle',
        'fashion' => 'Fashion',
        'beauty' => 'Beauty',
        'food' => 'Food',
        'fitness' => 'Fitness',
        'tech' => 'Tech',
        'travel' => 'Travel',
        'parenting' => 'Parenting',
        'finance' => 'Finance',
        'education' => 'Education',
        'other' => 'Other',
    ],

    'services' => [
        'content_creation' => 'Content Creation',
        'social_media_management' => 'Social Media Management',
        'community_management' => 'Community Management',
        'paid_ads' => 'Paid Ads',
        'graphic_design' => 'Graphic Design',
        'video_editing' => 'Video Editing',
        'strategy_consulting' => 'Strategy Consulting',
        'other' => 'Other',
    ],
];
