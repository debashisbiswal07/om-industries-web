import { HttpInterceptorFn } from '@angular/common/http';

export const AuthInterceptor: HttpInterceptorFn = (req, next) => {
  //return next(req);
  const modifiedRequest = req.clone({
    setHeaders: {
      'Authorization': 'Bearer my-token'
    }
  });

  // Handle the request and return the response
  return next(modifiedRequest);
};
