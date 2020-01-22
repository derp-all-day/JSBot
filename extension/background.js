function isCSPHeader(headerName) {
  return (headerName === 'CONTENT-SECURITY-POLICY') || (headerName === 'X-WEBKIT-CSP');
}

// X-Frame
chrome.webRequest.onHeadersReceived.addListener(
  function (details) {
    for (var i = 0; i < details.responseHeaders.length; ++i) {
      if (details.responseHeaders[i].name.toLowerCase() == 'x-frame-options') {
	console.log("X-Frame header [" + i + "] " + details.responseHeaders[i]);
        details.responseHeaders.splice(i, 1);
        return {
          responseHeaders: details.responseHeaders
        };
      }
    }
  }, {
    urls: ["<all_urls>"]
  }, ["blocking", "responseHeaders"]);

// CSP
chrome.webRequest.onHeadersReceived.addListener((details) => {
  for (let i = 0; i < details.responseHeaders.length; i += 1) {
    if (isCSPHeader(details.responseHeaders[i].name.toUpperCase())) {
      console.log("CSP [" + i + "] " + details.responseHeaders[i].name.toUpperCase());
      const csp = 'default-src * \'unsafe-inline\' \'unsafe-eval\' data: blob:; ';
      details.responseHeaders[i].value = csp;
    }
  }
  return { // Return the new HTTP header
    responseHeaders: details.responseHeaders,
  };
}, {
  urls: ['<all_urls>'],
  types: ['main_frame'],
}, ['blocking', 'responseHeaders']);
