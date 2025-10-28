export const getters = {
  checkModule: (state) => (module) => {
    const { list } = state;

    if (!list) {
      return undefined;
    }

    return list.includes(module);
  },
  moduleExist() {
    return (key) => {
      return this.checkModule(key);
    };
  },
};
