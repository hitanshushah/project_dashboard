
export const getLinkIcon = (title: string): string => {
  const titleLower = title.toLowerCase();
  if (titleLower.includes('github')) {
    return 'mdi-github';
  } else if (titleLower.includes('live') || titleLower.includes('demo') || titleLower.includes('url')) {
    return 'mdi-link-variant';
  } else {
    return 'mdi-web';
  }
};

export const getLinkIconColor = (title: string): string => {
  const titleLower = title.toLowerCase();
  if (titleLower.includes('github')) {
    return 'black';
  } else if (titleLower.includes('live') || titleLower.includes('demo') || titleLower.includes('url')) {
    return 'green';
  } else {
    return 'blue';
  }
};

export const getStatusColor = (status: string): string => {
  const colorMap: Record<string, string> = {
    'planning': 'blue',
    'inprogress': 'orange',
    'finished': 'green',
    'onhold': 'yellow',
    'cancelled': 'red',
    'notstarted': 'grey'
  };
  return colorMap[status] || 'grey';
};

export const getStatusName = (statusKey: string, statuses?: Array<{ name: string; key: string }>): string => {
  if (!statuses) return statusKey;
  const status = statuses.find(s => s.key === statusKey);
  return status?.name || statusKey;
};

export const getCategoryName = (categoryKey: string, categories?: Array<{ name: string; key: string }>): string => {
  if (!categories) return categoryKey;
  const category = categories.find(c => c.key === categoryKey);
  return category?.name || categoryKey;
};

export const formatDate = (dateString: string): string => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    month: 'short',
    year: 'numeric'
  });
};

export const formatDateTime = (dateString: string): string => {
  if (!dateString) return '';
  return new Date(dateString).toLocaleString();
};

export const getFileIcon = (fileType: string): string => {
  if (fileType.startsWith('image/')) return 'mdi-image';
  if (fileType.includes('pdf')) return 'mdi-file-pdf-box';
  if (fileType.includes('word') || fileType.includes('doc')) return 'mdi-file-word-box';
  if (fileType.includes('excel') || fileType.includes('sheet')) return 'mdi-file-excel-box';
  if (fileType.includes('zip') || fileType.includes('rar')) return 'mdi-folder-zip';
  if (fileType.includes('text')) return 'mdi-file-document';
  return 'mdi-file';
};

export const getFileColor = (fileType: string): string => {
  if (fileType.startsWith('image/')) return 'green';
  if (fileType.includes('pdf')) return 'red';
  if (fileType.includes('word') || fileType.includes('doc')) return 'blue';
  if (fileType.includes('excel') || fileType.includes('sheet')) return 'green';
  if (fileType.includes('zip') || fileType.includes('rar')) return 'orange';
  return 'grey';
};

export const getFileType = (fileType: string): string => {
  if (fileType.startsWith('image/')) return 'Image';
  if (fileType.includes('pdf')) return 'PDF';
  if (fileType.includes('word') || fileType.includes('doc')) return 'Word Document';
  if (fileType.includes('excel') || fileType.includes('sheet')) return 'Excel Spreadsheet';
  if (fileType.includes('zip') || fileType.includes('rar')) return 'Archive';
  if (fileType.includes('text')) return 'Text File';
  return 'File';
};

export const formatFileSize = (bytes: number): string => {
  if (!bytes) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};

export const isPreviewable = (fileType: string): boolean => {
  return fileType.startsWith('image/') || fileType.includes('pdf') || fileType.includes('text');
};

export const getFileUrl = (file: any): string => {
  
  if (file instanceof File) {
    return URL.createObjectURL(file);
  }
  
  if (file.url) {
    return file.url;
  }
  
  if (file.path) {
    return file.path;
  }
  
  if (file.filename) {
    return file.filename;
  }
  
  return '';
};

export const getUserForPublicProjects = async (): Promise<number> => {
  const response = await fetch('/api/public-projects/user');
  if (!response.ok) {
    throw new Error('Failed to get user information');
  }
  
  const data = await response.json();
  return data.user_id;
};

export const getPublicProjectsByUserId = async (userId: number, filters?: {
  search?: string;
  categories?: string[];
  statuses?: string[];
  technologies?: string[];
  sort_by?: string;
  sort_direction?: string;
}): Promise<any> => {
  const params = new URLSearchParams();
  
  if (filters) {
    if (filters.search) params.append('search', filters.search);
    if (filters.categories) filters.categories.forEach(cat => params.append('categories[]', cat));
    if (filters.statuses) filters.statuses.forEach(status => params.append('statuses[]', status));
    if (filters.technologies) filters.technologies.forEach(tech => params.append('technologies[]', tech));
    if (filters.sort_by) params.append('sort_by', filters.sort_by);
    if (filters.sort_direction) params.append('sort_direction', filters.sort_direction);
  }
  
  const url = `/api/public-projects/${userId}${params.toString() ? '?' + params.toString() : ''}`;
  const response = await fetch(url);
  
  if (!response.ok) {
    throw new Error('Failed to get public projects data');
  }
  
  return await response.json();
};

export const openPublicProjectsPage = (userId: number): void => {
  window.open(`/public-projects/${userId}`, '_blank');
};

export const getCurrentUserPublicProjectsUrl = async (): Promise<string> => {
  const userId = await getUserForPublicProjects();
  return `/public-projects/${userId}`;
}; 