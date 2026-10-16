export type IncidentStatus = 'open' | 'in_review' | 'blocked' | 'resolved';
export type IncidentPriority = 'low' | 'medium' | 'high';

export type Incident = {
    id: number;
    title: string;
    description: string;
    status: IncidentStatus;
    priority: IncidentPriority;
    requester_name: string;
    assigned_to: string | null;
    created_at: string | null;
    updated_at: string | null;
};

export type IncidentFilters = {
    status: IncidentStatus | null;
    priority: IncidentPriority | null;
    q: string;
};

export type IncidentPagination = {
    data: Incident[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

export type CreateIncidentValues = {
    title: string;
    description: string;
    priority: IncidentPriority;
    requester_name: string;
    assigned_to: string;
};
