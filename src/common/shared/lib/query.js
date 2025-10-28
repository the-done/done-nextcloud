const isObject = (value) =>
  typeof value === "object" && !Array.isArray(value) && value !== null;

const encodeQueryParam = (decoded) =>
  encodeURIComponent(decoded).replace(/%20/g, "+");

const encodeObject = (params, prefix) => {
  let encodedParams = [];

  Object.keys(params).forEach((name) => {
    let paramName = name;
    const paramValue = params[name];

    if (prefix !== "") {
      if (params instanceof Array) {
        paramName = prefix + "[]";
      } else {
        paramName = prefix + `[${name}]`;
      }
    }

    if (isObject(paramValue) === true) {
      encodedParams = encodedParams.concat(encodeObject(paramValue, paramName));
    } else {
      encodedParams.push(
        encodeQueryParam(paramName) + "=" + encodeQueryParam(paramValue)
      );
    }
  });

  return encodedParams;
};

export const queryEncode = (params) => {
  const encoded = encodeObject(params, "");

  return encoded.join("&");
};
