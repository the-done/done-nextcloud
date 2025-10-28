export const getters = {
  filterConditionsFetched: (state) => state?.conditions?.isFetched || false,
  filterConditionsList: (state) => state?.conditions?.list || null,
};
