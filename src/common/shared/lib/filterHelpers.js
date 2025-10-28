export const initFilterDescriptor = (descriptor) => {
  return descriptor.map((item) => {
    if (item.fetchOptionsFunction !== undefined) {
      return {
        ...item,
        initiated: false,
      };
    }

    return item;
  });
};
