import { ref } from 'vue';
import { 
  getUserForPublicProjects, 
  getPublicProjectsByUserId, 
  openPublicProjectsPage, 
  getCurrentUserPublicProjectsUrl 
} from '@/lib/projectUtils';

export function usePublicProjects() {
  const loading = ref(false);
  const error = ref<string | null>(null);

    const getCurrentUserId = async (): Promise<number | null> => {
    loading.value = true;
    error.value = null;
    
    try {
      const userId = await getUserForPublicProjects();
      return userId;
    } catch (err) {
      error.value = 'Failed to get user information';
      console.error('Error getting user ID:', err);
      return null;
    } finally {
      loading.value = false;
    }
  };

    const getProjectsData = async (userId: number, filters?: {
    search?: string;
    categories?: string[];
    statuses?: string[];
    technologies?: string[];
    sort_by?: string;
    sort_direction?: string;
  }) => {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await getPublicProjectsByUserId(userId, filters);
      return data;
    } catch (err) {
      error.value = 'Failed to get projects data';
      console.error('Error getting projects data:', err);
      return null;
    } finally {
      loading.value = false;
    }
  };

    const openProjectsPage = (userId: number) => {
    openPublicProjectsPage(userId);
  };

    const getCurrentUserUrl = async (): Promise<string | null> => {
    loading.value = true;
    error.value = null;
    
    try {
      const url = await getCurrentUserPublicProjectsUrl();
      return url;
    } catch (err) {
      error.value = 'Failed to get public projects URL';
      console.error('Error getting URL:', err);
      return null;
    } finally {
      loading.value = false;
    }
  };

    const getPublicProjectsFlow = async (openInNewTab = false) => {
    loading.value = true;
    error.value = null;
    
    try {
      
      const userId = await getUserForPublicProjects();
      if (!userId) {
        throw new Error('User not found');
      }

      
      const data = await getPublicProjectsByUserId(userId);
      
      
      if (openInNewTab) {
        openPublicProjectsPage(userId);
      }

      return {
        userId,
        data,
        url: `/public-projects/${userId}`
      };
    } catch (err) {
      error.value = 'Failed to complete public projects flow';
      console.error('Error in public projects flow:', err);
      return null;
    } finally {
      loading.value = false;
    }
  };

  return {
    loading,
    error,
    getCurrentUserId,
    getProjectsData,
    openProjectsPage,
    getCurrentUserUrl,
    getPublicProjectsFlow
  };
}
