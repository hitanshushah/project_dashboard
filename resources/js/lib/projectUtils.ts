/**
 * Shared utility functions for project-related components
 */

/**
 * Get the appropriate icon for a link based on its title
 */
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

/**
 * Get the appropriate color for a link icon based on its title
 */
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

/**
 * Get the appropriate color for a status
 */
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

/**
 * Get the status name from status key
 */
export const getStatusName = (statusKey: string, statuses?: Array<{ name: string; key: string }>): string => {
  if (!statuses) return statusKey;
  const status = statuses.find(s => s.key === statusKey);
  return status?.name || statusKey;
};

/**
 * Get the category name from category key
 */
export const getCategoryName = (categoryKey: string, categories?: Array<{ name: string; key: string }>): string => {
  if (!categories) return categoryKey;
  const category = categories.find(c => c.key === categoryKey);
  return category?.name || categoryKey;
};

/**
 * Format a date string to locale date format
 */
export const formatDate = (dateString: string): string => {
  if (!dateString) return '';
  return new Date(dateString).toLocaleDateString();
};

/**
 * Format a date string to locale date and time format
 */
export const formatDateTime = (dateString: string): string => {
  if (!dateString) return '';
  return new Date(dateString).toLocaleString();
};

/**
 * Get file icon based on file type
 */
export const getFileIcon = (fileType: string): string => {
  if (fileType.startsWith('image/')) return 'mdi-image';
  if (fileType.includes('pdf')) return 'mdi-file-pdf-box';
  if (fileType.includes('word') || fileType.includes('doc')) return 'mdi-file-word-box';
  if (fileType.includes('excel') || fileType.includes('sheet')) return 'mdi-file-excel-box';
  if (fileType.includes('zip') || fileType.includes('rar')) return 'mdi-folder-zip';
  if (fileType.includes('text')) return 'mdi-file-document';
  return 'mdi-file';
};

/**
 * Get file color based on file type
 */
export const getFileColor = (fileType: string): string => {
  if (fileType.startsWith('image/')) return 'green';
  if (fileType.includes('pdf')) return 'red';
  if (fileType.includes('word') || fileType.includes('doc')) return 'blue';
  if (fileType.includes('excel') || fileType.includes('sheet')) return 'green';
  if (fileType.includes('zip') || fileType.includes('rar')) return 'orange';
  return 'grey';
};

/**
 * Get file type description based on file type
 */
export const getFileType = (fileType: string): string => {
  if (fileType.startsWith('image/')) return 'Image';
  if (fileType.includes('pdf')) return 'PDF';
  if (fileType.includes('word') || fileType.includes('doc')) return 'Word Document';
  if (fileType.includes('excel') || fileType.includes('sheet')) return 'Excel Spreadsheet';
  if (fileType.includes('zip') || fileType.includes('rar')) return 'Archive';
  if (fileType.includes('text')) return 'Text File';
  return 'File';
};

/**
 * Format file size in bytes to human readable format
 */
export const formatFileSize = (bytes: number): string => {
  if (!bytes) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};

/**
 * Check if a file is previewable
 */
export const isPreviewable = (fileType: string): boolean => {
  return fileType.startsWith('image/') || fileType.includes('pdf') || fileType.includes('text');
};

/**
 * Get file URL for display
 */
export const getFileUrl = (file: any): string => {
  // For File objects (from form), create object URL
  if (file instanceof File) {
    return URL.createObjectURL(file);
  }
  // For saved files, use the url property if available (MinIO URLs)
  if (file.url) {
    return file.url;
  }
  // For saved files, use the path
  if (file.path) {
    return file.path;
  }
  // For MinIO files stored in filename field
  if (file.filename) {
    return file.filename;
  }
  // Fallback
  return '';
}; 