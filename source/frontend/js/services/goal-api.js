/**
 * WealthFlow Financial Goals API Service
 * Interacts with /api/goals endpoints
 */
import { httpClient } from './http-client.js';

export const GoalApi = {
  getGoals() {
    return httpClient('goals');
  },

  createGoal(data) {
    return httpClient('goals', {
      method: 'POST',
      data
    });
  },

  allocateFunds(goalId, data) {
    return httpClient(`goals/${goalId}/deposit`, {
      method: 'POST',
      data
    });
  },

  withdrawFunds(goalId, data) {
    return httpClient(`goals/${goalId}/withdraw`, {
      method: 'POST',
      data
    });
  },

  completeGoal(goalId) {
    return httpClient(`goals/${goalId}/complete`, {
      method: 'POST'
    });
  },

  cancelGoal(goalId) {
    return httpClient(`goals/${goalId}`, {
      method: 'DELETE'
    });
  }
};
