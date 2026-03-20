import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PinImageRendererComponent } from './pin-image-renderer.component';

describe('PinImageRendererComponent', () => {
  let component: PinImageRendererComponent;
  let fixture: ComponentFixture<PinImageRendererComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PinImageRendererComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PinImageRendererComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
